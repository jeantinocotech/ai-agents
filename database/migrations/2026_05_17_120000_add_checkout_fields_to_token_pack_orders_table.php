<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('token_pack_orders', function (Blueprint $table) {
            $table->string('payment_method', 24)->nullable()->after('status');
            $table->string('bank_slip_url', 512)->nullable()->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('token_pack_orders', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'bank_slip_url']);
        });
    }
};
