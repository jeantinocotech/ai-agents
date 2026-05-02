<?php

use App\Models\CareerTrailStep;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        CareerTrailStep::query()->where('slug', 'cover-letter')->update(['title' => 'Motivação']);
        CareerTrailStep::query()->where('slug', 'interviews')->update(['title' => 'Entrevista']);
        CareerTrailStep::query()->where('slug', 'first-100-days')->update(['title' => 'Primeiros 100 dias']);
    }

    public function down(): void
    {
        CareerTrailStep::query()->where('slug', 'cover-letter')->update(['title' => 'Carta de apresentação']);
        CareerTrailStep::query()->where('slug', 'interviews')->update(['title' => 'Preparar entrevistas']);
        CareerTrailStep::query()->where('slug', 'first-100-days')->update(['title' => 'Estratégia dos primeiros 100 dias']);
    }
};
