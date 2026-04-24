<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('token_balance')->default(0);
            $table->timestamp('tokens_next_renewal_at')->nullable();
        });

        DB::table('users')->whereNull('tokens_next_renewal_at')->update([
            'tokens_next_renewal_at' => now()->addDays(30),
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['token_balance', 'tokens_next_renewal_at']);
        });
    }
};
