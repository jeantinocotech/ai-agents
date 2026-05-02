<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('motivation_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('jd_document_id')->constrained('agent_documents')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->longText('body');
            $table->string('source', 24)->default('manual');
            $table->timestamps();

            $table->unique(['user_id', 'jd_document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('motivation_letters');
    }
};
