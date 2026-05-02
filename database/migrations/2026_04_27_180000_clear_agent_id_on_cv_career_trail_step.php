<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * O passo «cv» deve usar CAREER_TRAIL_CV_CHATKIT_AGENT_ID; valores antigos em agent_id
     * podiam apontar por engano para o assistente ATS (CV+JD).
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
