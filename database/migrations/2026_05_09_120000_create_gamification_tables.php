<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gamification_badge_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('key', 48)->unique(); // ex.: cv, ats, motivation, interviews
            $table->string('label', 64);
            $table->string('counter_label', 96)->nullable(); // ex.: "CVs criados"
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('gamification_badge_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('badge_definition_id')->constrained('gamification_badge_definitions')->cascadeOnDelete();
            $table->unsignedInteger('threshold'); // ex.: 1,3,10,20,30
            $table->string('title', 64);
            $table->string('icon_key', 64)->nullable(); // emoji, heroicon key, etc.
            $table->string('color_key', 32)->nullable();
            $table->timestamps();

            $table->unique(['badge_definition_id', 'threshold']);
            $table->index(['badge_definition_id', 'threshold']);
        });

        Schema::create('gamification_score_event_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique(); // ex.: chatkit_cv_turn
            $table->string('label', 96);
            $table->integer('points'); // pode ser negativo se for necessário no futuro
            $table->unsignedInteger('daily_cap')->nullable(); // null = sem cap
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('gamification_ranks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('min_points')->unique();
            $table->string('title', 64);
            $table->string('icon_key', 64)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('gamification_score_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('event_key', 64);
            $table->string('reference_type', 120)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'occurred_at']);
            $table->index(['user_id', 'event_key', 'occurred_at']);
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('user_gamification_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('score_total')->default(0);
            $table->foreignId('rank_id')->nullable()->constrained('gamification_ranks')->nullOnDelete();
            $table->json('badges_state')->nullable(); // {cv:{count,level_threshold,title,icon_key,next_threshold}, ...}
            $table->string('definitions_hash', 64)->nullable();
            $table->timestamp('computed_at')->nullable();
            $table->timestamp('last_event_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });

        // Seed: badge definitions + levels (defaults editáveis no admin)
        $now = now();
        $badgeRows = [
            ['key' => 'cv', 'label' => 'CV', 'counter_label' => 'CVs criados', 'sort_order' => 10, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'ats', 'label' => 'ATS', 'counter_label' => 'Vagas pareadas (JD+CV)', 'sort_order' => 20, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'motivation', 'label' => 'Motivação', 'counter_label' => 'Cartas salvas', 'sort_order' => 30, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'interviews', 'label' => 'Entrevistas', 'counter_label' => 'Rondas salvas', 'sort_order' => 40, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ];
        DB::table('gamification_badge_definitions')->insert($badgeRows);
        $badgeIds = DB::table('gamification_badge_definitions')->pluck('id', 'key')->all();

        $thresholds = [
            1 => ['cv' => 'Rascunhista', 'ats' => 'Triador', 'motivation' => 'Esboço', 'interviews' => 'Aquecimento'],
            3 => ['cv' => 'Revisor', 'ats' => 'Ajustador', 'motivation' => 'Narrador', 'interviews' => 'Pronto'],
            10 => ['cv' => 'Editor', 'ats' => 'Alinhador', 'motivation' => 'Persuasor', 'interviews' => 'Confiante'],
            20 => ['cv' => 'Curador', 'ats' => 'Estrategista', 'motivation' => 'Orador', 'interviews' => 'Consistente'],
            30 => ['cv' => 'Arquiteto do CV', 'ats' => 'Maestro do Fit', 'motivation' => 'Voz de Marca', 'interviews' => 'Nato'],
        ];
        $levelRows = [];
        foreach ($thresholds as $threshold => $titles) {
            foreach ($titles as $badgeKey => $title) {
                $levelRows[] = [
                    'badge_definition_id' => (int) ($badgeIds[$badgeKey] ?? 0),
                    'threshold' => (int) $threshold,
                    'title' => $title,
                    'icon_key' => null,
                    'color_key' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        DB::table('gamification_badge_levels')->insert($levelRows);

        // Seed: score event definitions (defaults editáveis no admin)
        DB::table('gamification_score_event_definitions')->insert([
            [
                'key' => 'chatkit_cv_turn',
                'label' => 'Consulta ChatKit (CV)',
                'points' => 8,
                'daily_cap' => 40,
                'is_active' => true,
                'description' => 'Consulta de CV (turno) no ChatKit.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'chatkit_ats_consultation',
                'label' => 'Consulta ChatKit (ATS)',
                'points' => 10,
                'daily_cap' => 50,
                'is_active' => true,
                'description' => 'Consulta ATS no ChatKit.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'cv_created',
                'label' => 'CV criado',
                'points' => 40,
                'daily_cap' => null,
                'is_active' => true,
                'description' => 'Novo CV criado pelo utilizador.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'ats_pair_created',
                'label' => 'Vaga pareada (JD+CV)',
                'points' => 60,
                'daily_cap' => null,
                'is_active' => true,
                'description' => 'Vaga (JD) ficou associada a um CV (pareamento).',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'motivation_letter_created',
                'label' => 'Carta de motivação criada',
                'points' => 35,
                'daily_cap' => null,
                'is_active' => true,
                'description' => 'Nova carta guardada.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'interview_process_created',
                'label' => 'Conseguiu entrevista (processo criado)',
                'points' => 300,
                'daily_cap' => null,
                'is_active' => true,
                'description' => 'Criou o processo de entrevistas para uma vaga (JD).',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'interview_round_created',
                'label' => 'Avanço em entrevistas (nova ronda)',
                'points' => 100,
                'daily_cap' => null,
                'is_active' => true,
                'description' => 'Nova ronda registada no mesmo processo.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'token_pack_purchased',
                'label' => 'Compra de tokens',
                'points' => 50,
                'daily_cap' => null,
                'is_active' => true,
                'description' => 'Compra de pacote de tokens concluída.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'process_approved',
                'label' => 'Aprovado (vaga)',
                'points' => 1000,
                'daily_cap' => null,
                'is_active' => true,
                'description' => 'Marcou aprovação final (libera proposta). Pontuação é stateful pelo estado do processo.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // Seed: ranks (defaults editáveis no admin)
        DB::table('gamification_ranks')->insert([
            ['min_points' => 0, 'title' => 'Visitante', 'icon_key' => null, 'sort_order' => 0, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['min_points' => 100, 'title' => 'Explorador', 'icon_key' => null, 'sort_order' => 10, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['min_points' => 300, 'title' => 'Aprendiz', 'icon_key' => null, 'sort_order' => 20, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['min_points' => 600, 'title' => 'Praticante', 'icon_key' => null, 'sort_order' => 30, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['min_points' => 900, 'title' => 'Estrategista', 'icon_key' => null, 'sort_order' => 40, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['min_points' => 1200, 'title' => 'Especialista', 'icon_key' => null, 'sort_order' => 50, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['min_points' => 1700, 'title' => 'Mentor', 'icon_key' => null, 'sort_order' => 60, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['min_points' => 2300, 'title' => 'Navegador', 'icon_key' => null, 'sort_order' => 70, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['min_points' => 3000, 'title' => 'Lenda', 'icon_key' => null, 'sort_order' => 80, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('user_gamification_snapshots');
        Schema::dropIfExists('gamification_score_events');
        Schema::dropIfExists('gamification_ranks');
        Schema::dropIfExists('gamification_score_event_definitions');
        Schema::dropIfExists('gamification_badge_levels');
        Schema::dropIfExists('gamification_badge_definitions');
    }
};
