<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ats_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_document_id')->constrained('agent_documents')->cascadeOnDelete();
            $table->foreignId('user_cv_id')->constrained('user_cvs')->cascadeOnDelete();
            $table->decimal('ats_score', 5, 2)->nullable();
            $table->decimal('previous_ats_score', 5, 2)->nullable();
            $table->string('status', 32)->default('ready');
            $table->json('summary')->nullable();
            $table->string('source', 32)->default('llm');
            $table->timestamps();

            $table->unique(['user_id', 'agent_document_id', 'user_cv_id'], 'ats_analyses_user_jd_cv_unique');
        });

        Schema::create('ats_analysis_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ats_analysis_id')->constrained('ats_analyses')->cascadeOnDelete();
            $table->string('keyword', 255);
            $table->string('relevance', 16)->default('medium');
            $table->string('match_status', 16)->default('missing');
            $table->text('cv_snippet')->nullable();
            $table->text('suggestion')->nullable();
            $table->boolean('is_addressed')->default(false);
            $table->unsignedSmallInteger('priority_rank')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['ats_analysis_id', 'priority_rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ats_analysis_items');
        Schema::dropIfExists('ats_analyses');
    }
};
