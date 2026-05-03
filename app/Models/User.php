<?php

namespace App\Models;

use App\Notifications\VerifyEmailNotification;
use App\Support\BrazilTaxId;
use App\Support\EmailVerificationCode;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'linkedin_url',
        'password',
        'asaas_customer_id',
        'profile_photo',
        'phone', 'cpf', 'cep', 'address', 'number', 'city', 'state',
        'privacy_accepted_at',
        'privacy_policy_accepted_version',
        'privacy_ip',
        'privacy_user_agent',
        'terms_accepted_at',
        'terms_accepted_version',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_admin' => 'boolean',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'tokens_next_renewal_at' => 'datetime',
            'privacy_accepted_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_hashes' => 'array',
        ];
    }

    /**
     * Garante um destinatário explícito para o canal mail (evita envio silenciosamente ignorado).
     *
     * @param  mixed  $notification
     */
    public function routeNotificationForMail($notification = null): ?string
    {
        $email = trim((string) ($this->attributes['email'] ?? $this->email ?? ''));

        return $email !== '' ? $email : null;
    }

    /**
     * Envio explícito da notificação de verificação, com registo se o e-mail estiver em falta.
     */
    public function sendEmailVerificationNotification(): void
    {
        $email = $this->routeNotificationForMail();

        if ($email === null || $email === '') {
            Log::warning('Não foi enviado e-mail de verificação: endereço em branco.', [
                'user_id' => $this->getKey(),
            ]);

            return;
        }

        $code = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);
        EmailVerificationCode::remember((int) $this->getKey(), $code);

        $this->notify(new VerifyEmailNotification($code));
    }

    public function hasAcceptedCurrentLegalDocuments(): bool
    {
        $pv = (string) config('legal.privacy_policy_version');
        $tv = (string) config('legal.terms_version');

        return $this->privacy_accepted_at !== null
            && $this->terms_accepted_at !== null
            && (string) ($this->privacy_policy_accepted_version ?? '') === $pv
            && (string) ($this->terms_accepted_version ?? '') === $tv;
    }

    /** Dados mínimos para Asaas e obrigações fiscais (Brasil). */
    public function hasCompleteBillingProfile(): bool
    {
        $cpf = BrazilTaxId::onlyDigits((string) ($this->cpf ?? ''));

        return trim((string) $this->phone) !== ''
            && BrazilTaxId::isValidCpfOrCnpj($cpf)
            && strlen(preg_replace('/\D/', '', (string) $this->cep)) === 8
            && trim((string) $this->address) !== ''
            && trim((string) $this->number) !== ''
            && trim((string) $this->city) !== ''
            && self::isValidBrazilStateCode((string) $this->state);
    }

    private static function isValidBrazilStateCode(string $raw): bool
    {
        $st = strtoupper(preg_replace('/\s+/', '', $raw) ?? '');

        return strlen($st) === 2 && ctype_alpha($st);
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function activePurchases()
    {
        return $this->hasMany(Purchase::class)->where('active', true);
    }

    public function hasActivePurchaseForAgent($agentId): bool
    {
        return $this->purchases()
            ->where('agent_id', $agentId)
            ->where('active', true)
            ->exists();
    }

    public function tokenTransactions()
    {
        return $this->hasMany(TokenTransaction::class);
    }

    public function tokenPackOrders()
    {
        return $this->hasMany(TokenPackOrder::class);
    }

    public function careerTrailProgress(): HasOne
    {
        return $this->hasOne(UserCareerTrailProgress::class);
    }

    public function userCvs(): HasMany
    {
        return $this->hasMany(UserCv::class);
    }
}
