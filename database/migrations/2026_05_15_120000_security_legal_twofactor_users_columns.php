<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('privacy_policy_accepted_version')->nullable()->after('privacy_user_agent');
            $table->timestamp('terms_accepted_at')->nullable()->after('privacy_policy_accepted_version');
            $table->string('terms_accepted_version')->nullable()->after('terms_accepted_at');
            $table->text('two_factor_secret')->nullable()->after('terms_accepted_version');
            $table->text('two_factor_recovery_hashes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_hashes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'privacy_policy_accepted_version',
                'terms_accepted_at',
                'terms_accepted_version',
                'two_factor_secret',
                'two_factor_recovery_hashes',
                'two_factor_confirmed_at',
            ]);
        });
    }
};
