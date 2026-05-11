<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_documents', function (Blueprint $table) {
            $table->string('application_status', 32)->nullable()->after('user_cv_id');
            $table->timestamp('ats_submitted_at')->nullable()->after('application_status');
        });

        if (Schema::hasColumn('agent_documents', 'user_cv_id')) {
            $ts = now();
            DB::table('agent_documents')
                ->where('type', 'jd')
                ->whereNotNull('user_cv_id')
                ->update([
                    'application_status' => 'submitted',
                    'ats_submitted_at' => $ts,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('agent_documents', function (Blueprint $table) {
            $table->dropColumn(['application_status', 'ats_submitted_at']);
        });
    }
};
