<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_preparations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('jd_document_id')->constrained('agent_documents')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->string('persona', 32);
            $table->string('status', 24);
            $table->longText('learnings')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'jd_document_id', 'sequence']);
            $table->index('persona');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_preparations');
    }
};
