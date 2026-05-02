<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_career_trail_progress', function (Blueprint $table) {
            $table->unsignedSmallInteger('max_sort_order_reached')->nullable()->after('started_at');
        });

        Schema::table('career_trail_steps', function (Blueprint $table) {
            $table->foreignId('agent_id')->nullable()->after('sort_order')->constrained('agents')->nullOnDelete();
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            foreach (DB::table('user_career_trail_progress')->cursor() as $row) {
                $sort = DB::table('career_trail_steps')->where('id', $row->current_step_id)->value('sort_order');
                if ($sort !== null) {
                    DB::table('user_career_trail_progress')->where('id', $row->id)->update([
                        'max_sort_order_reached' => $sort,
                    ]);
                }
            }
        } else {
            DB::statement(
                'UPDATE user_career_trail_progress AS p
                INNER JOIN career_trail_steps AS s ON s.id = p.current_step_id
                SET p.max_sort_order_reached = s.sort_order
                WHERE p.max_sort_order_reached IS NULL'
            );
        }
    }

    public function down(): void
    {
        Schema::table('career_trail_steps', function (Blueprint $table) {
            $table->dropForeign(['agent_id']);
            $table->dropColumn('agent_id');
        });

        Schema::table('user_career_trail_progress', function (Blueprint $table) {
            $table->dropColumn('max_sort_order_reached');
        });
    }
};
