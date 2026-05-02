<?php

use App\Models\CareerTrailStep;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        CareerTrailStep::query()->where('slug', 'offer')->update(['title' => 'Proposta']);
    }

    public function down(): void
    {
        CareerTrailStep::query()->where('slug', 'offer')->update(['title' => 'Avaliar e negociar proposta']);
    }
};
