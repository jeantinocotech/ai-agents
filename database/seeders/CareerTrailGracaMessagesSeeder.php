<?php

namespace Database\Seeders;

use App\Models\CareerTrailGracaMessage;
use App\Models\CareerTrailStep;
use App\Support\CareerTrailGracaSlots;
use Illuminate\Database\Seeder;

/**
 * Textos «avatar + corpo» da Graça. Sincroniza o cabeçalho de cada passo a partir de career_trail_steps.graca_guidance
 * quando ainda não existir mensagem; garante slots da landing e da página Meu CV.
 */
class CareerTrailGracaMessagesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (CareerTrailStep::query()->orderBy('sort_order')->get() as $step) {
            $guidance = trim((string) ($step->graca_guidance ?? ''));
            if ($guidance === '') {
                continue;
            }

            CareerTrailGracaMessage::query()->updateOrCreate(
                [
                    'process_key' => 'career_trail',
                    'career_trail_step_id' => $step->id,
                    'slot' => CareerTrailGracaSlots::TRAIL_STEP_HEADER,
                    'sort_order' => 0,
                ],
                [
                    'body' => $guidance,
                    'is_active' => true,
                ]
            );
        }

        $cvStep = CareerTrailStep::query()->where('slug', 'cv')->first();
        if ($cvStep !== null) {
            $cvIntro = <<<'TXT'
Pode guardar vários CVs na conta e escolher qual é o predefinido — é esse que a trilha usa para avançar (texto com pelo menos __MIN_CHARS__ caracteres) e que os assistentes tratam como «CV do perfil». CVs criados na biblioteca ATS também pode copiar ou gerir aqui.
TXT;

            CareerTrailGracaMessage::query()->updateOrCreate(
                [
                    'process_key' => 'career_trail',
                    'career_trail_step_id' => $cvStep->id,
                    'slot' => CareerTrailGracaSlots::CV_PAGE_INTRO,
                    'sort_order' => 0,
                ],
                [
                    'body' => trim($cvIntro),
                    'is_active' => true,
                ]
            );
        }

        $authIntro = <<<'TXT'
Como é a primeira vez que nos cruzamos aqui, deixe-me apresentar-me: eu sou a orientadora da sua trilha de carreira na GratoAI. Acompanho-o passo a passo — do currículo a entrevistas e propostas — e ajudo-o a perceber o que fazer em cada etapa, em conjunto com os assistentes de IA quando fizer sentido.

Não precisa de memorizar nada: volte a falar comigo no mapa da trilha sempre que quiser recolher rumo.
TXT;

        CareerTrailGracaMessage::query()->updateOrCreate(
            [
                'process_key' => 'career_trail',
                'career_trail_step_id' => null,
                'slot' => CareerTrailGracaSlots::LANDING_AUTH_INTRO,
                'sort_order' => 0,
            ],
            [
                'body' => trim($authIntro),
                'is_active' => true,
            ]
        );

        $guestHero = <<<'TXT'
Deixe o CV passar no ATS, prepare entrevistas e negocie propostas — passo a passo, com a Sra. Graça a orientar e assistentes de IA em cada etapa.
TXT;

        CareerTrailGracaMessage::query()->updateOrCreate(
            [
                'process_key' => 'career_trail',
                'career_trail_step_id' => null,
                'slot' => CareerTrailGracaSlots::LANDING_GUEST_HERO,
                'sort_order' => 0,
            ],
            [
                'body' => trim($guestHero),
                'is_active' => true,
            ]
        );
    }
}
