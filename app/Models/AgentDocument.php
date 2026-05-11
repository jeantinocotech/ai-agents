<?php

namespace App\Models;

use App\Enums\JobApplicationStatus;
use App\Services\GamificationService;
use App\Services\JobApplicationStatusSync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class AgentDocument extends Model
{
    public const TYPE_CV = 'cv';

    public const TYPE_JD = 'jd';

    protected $fillable = [
        'user_id',
        'agent_id',
        'type',
        'title',
        'body',
        'paired_cv_document_id',
        'user_cv_id',
        'application_status',
        'ats_submitted_at',
        'cv_sent_to_employer_at',
    ];

    protected function casts(): array
    {
        return [
            'application_status' => JobApplicationStatus::class,
            'ats_submitted_at' => 'datetime',
            'cv_sent_to_employer_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (AgentDocument $doc) {
            // Simplificação: CVs vivem em user_cvs; agent_documents guarda JDs e referência opcional (user_cv_id).
            // Mantemos paired_cv_document_id apenas por compatibilidade retroativa.
            if ($doc->type === self::TYPE_CV) {
                $doc->paired_cv_document_id = null;
                $doc->user_cv_id = null;
            }

            if ($doc->type === self::TYPE_JD) {
                // Fluxo ChatKit/API: JD chega com paired_cv_document_id (CV em agent_documents). A trilha e a UI ATS
                // usam user_cv_id — copiamos o CV de perfil predefinido quando há par válido sem user_cv_id.
                if ($doc->user_cv_id === null && $doc->paired_cv_document_id !== null) {
                    $pairedId = (int) $doc->paired_cv_document_id;
                    if ($pairedId > 0) {
                        $pairedCv = self::query()
                            ->whereKey($pairedId)
                            ->where('user_id', $doc->user_id)
                            ->where('agent_id', $doc->agent_id)
                            ->where('type', self::TYPE_CV)
                            ->first();

                        if ($pairedCv !== null) {
                            $defaultProfileCv = UserCv::defaultForUserId((int) $doc->user_id);
                            if ($defaultProfileCv !== null) {
                                $doc->user_cv_id = $defaultProfileCv->id;
                            }
                        }
                    }
                }

                $doc->paired_cv_document_id = null;

                if ($doc->user_cv_id !== null) {
                    $ok = UserCv::query()
                        ->whereKey($doc->user_cv_id)
                        ->where('user_id', $doc->user_id)
                        ->exists();
                    if (! $ok) {
                        throw ValidationException::withMessages([
                            'user_cv_id' => 'O CV associado é inválido.',
                        ]);
                    }
                }

                if ($doc->isDirty('user_cv_id')) {
                    $doc->ats_submitted_at = null;
                    $doc->cv_sent_to_employer_at = null;
                }
            }
        });

        static::saved(function (AgentDocument $doc) {
            // Pareamento ATS: quando um JD passa a ter user_cv_id, conta como vaga pareada.
            if ($doc->type !== self::TYPE_JD) {
                return;
            }

            $was = $doc->getOriginal('user_cv_id');
            $now = $doc->user_cv_id;
            if ($was === null && $now !== null) {
                $user = User::query()->find((int) $doc->user_id);
                if ($user) {
                    app(GamificationService::class)->recordEvent(
                        $user,
                        'ats_pair_created',
                        self::class,
                        (int) $doc->id,
                        ['agent_id' => (int) $doc->agent_id, 'user_cv_id' => (int) $now]
                    );
                    app(GamificationService::class)->ensureFreshSnapshot($user);
                }
            }

            JobApplicationStatusSync::reconcile($doc);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function pairedCv(): BelongsTo
    {
        return $this->belongsTo(self::class, 'paired_cv_document_id');
    }

    public function jdsUsingThisCv(): HasMany
    {
        return $this->hasMany(self::class, 'paired_cv_document_id');
    }

    public function userCv(): BelongsTo
    {
        return $this->belongsTo(UserCv::class, 'user_cv_id');
    }
}
