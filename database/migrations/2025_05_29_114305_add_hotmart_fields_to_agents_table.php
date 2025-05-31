<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->string('hotmart_checkout_url')->nullable();
            $table->string('hotmart_product_id')->nullable()->index();
        });
    }
    
    public function down()
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn(['hotmart_checkout_url', 'hotmart_product_id']);
        });
    }
    
};
