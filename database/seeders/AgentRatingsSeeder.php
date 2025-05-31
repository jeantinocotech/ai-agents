<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AgentRating;
use App\Models\User;
use App\Models\Agent;
use App\Models\ChatSession;

class AgentRatingsSeeder extends Seeder
{
    public function run(): void
    {
        // Certifique-se de ter usuários, agentes e sessões no banco
        $users = User::all();
        $agents = Agent::all();
        $sessions = ChatSession::all();


        if ($users->isEmpty() || $agents->isEmpty() || $sessions->isEmpty()) {
            $this->command->warn("Usuários, agentes ou sessões não encontrados. Adicione dados antes de rodar esta seed.");
            return;
        }

        for ($i = 0; $i < 100; $i++) {
            $user = $users->random();
            $agent = $agents->random();
            $session = $sessions->random();

            AgentRating::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'agent_id' => $agent->id,
                    'chat_session_id' => $session->id,
                ],
                [
                    'rating' => rand(1, 5),
                    'comment' => fake()->sentence(),
                ]
            );
        }
    }
}
