<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_documents', function (Blueprint $table) {
            $table->timestamp('cv_sent_to_employer_at')->nullable()->after('ats_submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('agent_documents', function (Blueprint $table) {
            $table->dropColumn('cv_sent_to_employer_at');
        });
    }
};
