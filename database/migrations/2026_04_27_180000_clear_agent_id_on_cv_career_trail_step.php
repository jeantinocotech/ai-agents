<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Limpou-se agent_id do passo «cv» porque associações antigas podiam apontar por engano
     * para o assistente ATS (CV+JD). O assistente correcto é ligado ao passo na administração de agentes.
     */
    public function up(): void
    {
        DB::table('career_trail_steps')->where('slug', 'cv')->update(['agent_id' => null]);
    }

    public function down(): void
    {
        // Irreversível sem backup do valor anterior.
    }
};
