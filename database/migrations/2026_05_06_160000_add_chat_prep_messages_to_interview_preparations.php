<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interview_preparations', function (Blueprint $table) {
            $table->longText('chat_prep_messages')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('interview_preparations', function (Blueprint $table) {
            $table->dropColumn('chat_prep_messages');
        });
    }
};
