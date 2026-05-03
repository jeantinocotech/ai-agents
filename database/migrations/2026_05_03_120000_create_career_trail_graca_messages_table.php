<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('career_trail_graca_messages')) {
            $indexNames = collect(DB::select('SHOW INDEX FROM `career_trail_graca_messages`'))
                ->pluck('Key_name')
                ->unique();

            if ($indexNames->contains('career_trail_graca_msg_unique_slot_order')) {
                return;
            }

            // Orphan table from a failed run (e.g. index name exceeded MySQL 64-char limit).
            Schema::drop('career_trail_graca_messages');
        }

        Schema::create('career_trail_graca_messages', function (Blueprint $table) {
            $table->id();
            $table->string('process_key', 64)->default('career_trail');
            $table->foreignId('career_trail_step_id')->nullable()->constrained('career_trail_steps')->nullOnDelete();
            $table->string('slot', 64);
            $table->longText('body')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // MySQL identifier limit 64 chars; default Laravel index name is too long.
            $table->index(
                ['process_key', 'career_trail_step_id', 'slot', 'is_active'],
                'ct_graca_msg_lookup_idx'
            );
            $table->unique(['process_key', 'career_trail_step_id', 'slot', 'sort_order'], 'career_trail_graca_msg_unique_slot_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_trail_graca_messages');
    }
};
