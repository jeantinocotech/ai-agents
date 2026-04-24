<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_agent_jds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->longText('body');
            $table->timestamps();

            $table->unique(['user_id', 'agent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_agent_jds');
    }
};
