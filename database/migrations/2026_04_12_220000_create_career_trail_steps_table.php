<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('career_trail_steps', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->unsignedSmallInteger('sort_order');
            $table->string('title');
            $table->text('short_description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_trail_steps');
    }
};
