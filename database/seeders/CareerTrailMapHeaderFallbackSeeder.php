<?php

namespace Database\Seeders;

use App\Models\CareerTrailGracaMessage;
use App\Models\CareerTrailStep;
use App\Support\CareerTrailGracaSlots;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Texto ao lado do avatar na página «Mapa da trilha» (/trilha): slot trail_step_header
 * ligado ao passo actual de cada utilizador.
 *
 * Não há um único passo na BD: para cada utilizador conta o passo em que está.
 * Este seeder garante uma linha em career_trail_graca_messages por cada etapa ACTIVA,
 * apenas quando essa linha AINDA NÃO existe (usa firstOrCreate).
 *
 * Corpo utilizado ao criar: graca_guidance da etapa, se vier preenchida; caso contrário
 * o texto abaixo (ajuste antes de executar php artisan db:seed --class=...).
 *
 * Corre normalmente APÓS CareerTrailStepsSeeder e ideally após CareerTrailGracaMessagesSeeder.
 */
final class CareerTrailMapHeaderFallbackSeeder extends Seeder
{
    /**
     * Fallback quando career_trail_steps.graca_guidance está vazio e não há mensagem no slot.
     */
    private const DEFAULT_TRAIL_STEP_HEADER_BODY = <<<'TXT'
Psicóloga, coaching e aconselhadora de carreira. Vou acompanhar cada etapa com calma e objetivos claros — começamos pelo essencial e avançamos no seu ritmo.
TXT;

    public function run(): void
    {
        if (! Schema::hasTable((new CareerTrailGracaMessage)->getTable())) {
            $this->command?->warn('Tabela career_trail_graca_messages inexistente. Execute migrações.');

            return;
        }

        foreach (CareerTrailStep::query()->where('is_active', true)->orderBy('sort_order')->cursor() as $step) {
            $fromStep = trim((string) ($step->graca_guidance ?? ''));
            $body = $fromStep !== '' ? $fromStep : trim(self::DEFAULT_TRAIL_STEP_HEADER_BODY);

            CareerTrailGracaMessage::query()->firstOrCreate(
                [
                    'process_key' => 'career_trail',
                    'career_trail_step_id' => $step->id,
                    'slot' => CareerTrailGracaSlots::TRAIL_STEP_HEADER,
                    'sort_order' => 0,
                ],
                [
                    'body' => $body,
                    'is_active' => true,
                ]
            );
        }

        $this->command?->info('Cabeçalhos Graça trail_step_header verificados para todos os passos activos.');
    }
}
