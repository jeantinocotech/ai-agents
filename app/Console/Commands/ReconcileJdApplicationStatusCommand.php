<?php

namespace App\Console\Commands;

use App\Models\AgentDocument;
use App\Services\JobApplicationStatusSync;
use Illuminate\Console\Command;

class ReconcileJdApplicationStatusCommand extends Command
{
    protected $signature = 'trail:reconcile-jd-status
                            {--user= : ID do utilizador (opcional)}
                            {--jd= : ID da vaga JD (opcional)}';

    protected $description = 'Alinha application_status das vagas (JD) com CV, envio ATS e entrevistas';

    public function handle(): int
    {
        $query = AgentDocument::query()
            ->where('type', AgentDocument::TYPE_JD);

        if ($userId = $this->option('user')) {
            $query->where('user_id', (int) $userId);
        }

        if ($jdId = $this->option('jd')) {
            $query->whereKey((int) $jdId);
        }

        $updated = 0;

        $query->orderBy('id')->chunkById(100, function ($jds) use (&$updated): void {
            foreach ($jds as $jd) {
                $before = $jd->application_status?->value;
                JobApplicationStatusSync::reconcile($jd);
                $jd->refresh();
                $after = $jd->application_status?->value;

                if ($before !== $after) {
                    $updated++;
                    $this->line(sprintf(
                        'JD #%d «%s»: %s → %s (user_cv_id=%s)',
                        $jd->id,
                        $jd->title ?: '—',
                        $before ?? 'null',
                        $after ?? 'null',
                        $jd->user_cv_id ?? 'null'
                    ));
                }
            }
        });

        $this->info("Concluído. {$updated} vaga(s) actualizadas.");

        return self::SUCCESS;
    }
}
