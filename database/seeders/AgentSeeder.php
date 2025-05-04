<?php

namespace Database\Seeders;

use App\Models\Agent;
use Illuminate\Database\Seeder;

class AgentSeeder extends Seeder
{
    public function run(): void
    {
        // Cria agentes de exemplo
        $agents = [
            [
                'name' => 'Assistente de Atendimento',
                'description' => 'Um assistente virtual especializado em atendimento ao cliente, capaz de responder perguntas frequentes e ajudar com problemas comuns.',
                'image_path' => 'agents/images/default_support.jpg',
                'video_path' => 'agents/videos/default_support.mp4',
                'model_type' => 'GPT-4',
                'price' => 1.99,
            ],
            [
                'name' => 'Tutor Educacional',
                'description' => 'Um assistente de IA focado em ajudar estudantes com dúvidas acadêmicas e explicações de conceitos complexos.',
                'image_path' => 'agents/images/default_tutor.jpg',
                'video_path' => 'agents/videos/default_tutor.mp4',
                'model_type' => 'GPT-3.5',
                'price' => 1.99,
            ],
            [
                'name' => 'Consultor Financeiro',
                'description' => 'Um especialista virtual em finanças pessoais e investimentos, oferecendo orientações e análises personalizadas.',
                'image_path' => 'agents/images/default_financial.jpg',
                'video_path' => 'agents/videos/default_financial.mp4',
                'model_type' => 'Claude',
                'price' => 1.99,
            ],
        ];

        foreach ($agents as $agent) {
            Agent::create($agent);
        }
    }
}