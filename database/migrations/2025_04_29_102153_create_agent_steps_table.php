<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('agent_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->onDelete('cascade');
            $table->unsignedInteger('step_order');
            $table->string('name'); // ex: "Receber CV"
            $table->string('required_input')->nullable(); // ex: 'cv', 'jd', 'texto_livre'
            $table->json('expected_keywords')->nullable(); // palavras que identificam esse input
            $table->text('system_message')->nullable(); // o que o agente deve dizer nesse passo
            $table->boolean('can_continue')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_steps');
    }
};
