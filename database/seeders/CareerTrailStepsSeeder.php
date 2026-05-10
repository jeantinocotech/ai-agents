<?php

namespace Database\Seeders;

use App\Models\CareerTrailStep;
use Illuminate\Database\Seeder;

class CareerTrailStepsSeeder extends Seeder
{
    public function run(): void
    {
        $steps = [
            [
                'slug' => 'cv',
                'sort_order' => 1,
                'title' => 'Curriculum',
                'short_description' => 'Estruture e refine o seu currículo para destacar competências e resultados alinhados às vagas que deseja.',
                'graca_guidance' => 'Comece por um rascunho honesto: experiências, formação e o que gosta de fazer. Pode usar o assistente de chat na página do CV ou colar texto — o importante é ter material para refinar.',
            ],
            [
                'slug' => 'ats',
                'sort_order' => 2,
                'title' => 'Passar no filtro ATS',
                'short_description' => 'Analise o CV face à descrição da vaga e otimize palavras-chave para sistemas de triagem automática.',
                'graca_guidance' => 'Aqui o foco é alinhar o seu CV com a vaga: palavras-chave, verbos de ação e clareza. Guarde a descrição da vaga (JD) na biblioteca e peça sugestões concretas no ATS check.',
            ],
            [
                'slug' => 'cover-letter',
                'sort_order' => 3,
                'title' => 'Motivação',
                'short_description' => 'Produza uma carta convincente e personalizada para cada candidatura.',
                'graca_guidance' => 'Uma boa carta liga a sua história à empresa: mostre que pesquisou o contexto e porque encaixa. Evite frases genéricas; prefira um exemplo real do seu percurso.',
            ],
            [
                'slug' => 'interviews',
                'sort_order' => 4,
                'title' => 'Entrevista',
                'short_description' => 'Treine com cenários de RH, pares, responsável pela vaga, equipa e liderança — com confiança e clareza.',
                'graca_guidance' => 'Prepare histórias STAR (situação, tarefa, ação, resultado) e antecipe perguntas difíceis. Simule em voz alta — a confiança vem da prática.',
            ],
            [
                'slug' => 'offer',
                'sort_order' => 5,
                'title' => 'Proposta',
                'short_description' => 'Entenda pacotes de remuneração e benefícios e prepare-se para negociar de forma profissional.',
                'graca_guidance' => 'Leia a proposta com calma: salário base, variáveis, benefícios e cláusulas. Anote dúvidas e prioridades antes de responder — negociar é normal e esperado.',
            ],
            [
                'slug' => 'first-100-days',
                'sort_order' => 6,
                'title' => 'Primeiros 100 dias',
                'short_description' => 'Planeje integração, quick wins e relacionamentos para um arranque sólido no novo cargo.',
                'graca_guidance' => 'Nos primeiros dias, ouça mais do que fala: perceba ritmos, expectativas e decisores. Defina 2–3 vitórias rápidas alinhadas com a equipa.',
            ],
        ];

        foreach ($steps as $row) {
            CareerTrailStep::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'sort_order' => $row['sort_order'],
                    'title' => $row['title'],
                    'short_description' => $row['short_description'],
                    'graca_guidance' => $row['graca_guidance'],
                    'is_active' => true,
                ]
            );
        }
    }
}
