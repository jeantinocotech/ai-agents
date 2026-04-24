<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agents')) {
            if (Schema::getConnection()->getDriverName() === 'sqlite' && Schema::hasColumn('agents', 'hotmart_product_id')) {
                DB::statement('DROP INDEX IF EXISTS agents_hotmart_product_id_index');
            }

            Schema::table('agents', function (Blueprint $table) {
                $drop = [];
                if (Schema::hasColumn('agents', 'hotmart_checkout_url')) {
                    $drop[] = 'hotmart_checkout_url';
                }
                if (Schema::hasColumn('agents', 'hotmart_product_id')) {
                    $drop[] = 'hotmart_product_id';
                }
                if ($drop !== []) {
                    $table->dropColumn($drop);
                }
            });
        }

        if (Schema::hasTable('purchases')) {
            if (Schema::getConnection()->getDriverName() === 'sqlite' && Schema::hasColumn('purchases', 'hotmart_subscription_code')) {
                DB::statement('DROP INDEX IF EXISTS purchases_hotmart_subscription_code_index');
            }

            Schema::table('purchases', function (Blueprint $table) {
                if (Schema::hasColumn('purchases', 'hotmart_subscription_code')) {
                    $table->dropColumn('hotmart_subscription_code');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->string('hotmart_checkout_url')->nullable();
            $table->string('hotmart_product_id')->nullable()->index();
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->string('hotmart_subscription_code')->nullable()->index();
        });
    }
};
