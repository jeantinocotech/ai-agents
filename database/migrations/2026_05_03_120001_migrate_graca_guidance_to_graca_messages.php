<?php

use App\Support\CareerTrailGracaSlots;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('career_trail_graca_messages') || ! Schema::hasTable('career_trail_steps')) {
            return;
        }

        $rows = DB::table('career_trail_steps')->select(['id', 'graca_guidance'])->get();
        foreach ($rows as $row) {
            $text = trim((string) ($row->graca_guidance ?? ''));
            if ($text === '') {
                continue;
            }

            $exists = DB::table('career_trail_graca_messages')
                ->where('process_key', 'career_trail')
                ->where('career_trail_step_id', $row->id)
                ->where('slot', CareerTrailGracaSlots::TRAIL_STEP_HEADER)
                ->where('sort_order', 0)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('career_trail_graca_messages')->insert([
                'process_key' => 'career_trail',
                'career_trail_step_id' => $row->id,
                'slot' => CareerTrailGracaSlots::TRAIL_STEP_HEADER,
                'body' => $text,
                'sort_order' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('career_trail_graca_messages')) {
            return;
        }

        DB::table('career_trail_graca_messages')
            ->where('process_key', 'career_trail')
            ->where('slot', CareerTrailGracaSlots::TRAIL_STEP_HEADER)
            ->delete();
    }
};
