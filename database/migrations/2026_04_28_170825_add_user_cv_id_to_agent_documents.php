<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('agent_documents', function (Blueprint $table) {
            $table->foreignId('user_cv_id')
                ->nullable()
                ->after('paired_cv_document_id')
                ->constrained('user_cvs')
                ->nullOnDelete();

            $table->index(['user_id', 'agent_id', 'type', 'user_cv_id'], 'agent_docs_user_agent_type_usercv');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_documents', function (Blueprint $table) {
            $table->dropIndex('agent_docs_user_agent_type_usercv');
            $table->dropConstrainedForeignId('user_cv_id');
        });
    }
};
