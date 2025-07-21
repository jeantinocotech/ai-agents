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
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('agent_id')->nullable()->constrained()->onDelete('cascade'); // se null, é geral
            $table->text('content');
            $table->string('author_name')->nullable(); // pode usar nome de exibição
            $table->string('author_role')->nullable(); // profissão, etc
            $table->string('author_image')->nullable(); // avatar, se quiser
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_featured')->default(false); // só se o admin marcar para aparecer na home
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
