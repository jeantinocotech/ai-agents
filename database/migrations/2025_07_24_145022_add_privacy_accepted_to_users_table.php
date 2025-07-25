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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('privacy_accepted_at')->nullable();
            $table->string('privacy_ip', 45)->nullable(); // Para guardar o IP
            $table->string('privacy_user_agent', 255)->nullable(); // Opcional: browser
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('privacy_accepted_at');
            $table->dropColumn('privacy_ip');
            $table->dropColumn('privacy_user_agent');
        });
    }
};
