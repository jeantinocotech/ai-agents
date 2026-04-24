<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->string('integration', 32)->default('openai')->after('model_type');
            $table->string('chatkit_workflow_id')->nullable()->after('integration');
            $table->string('chatkit_workflow_version', 16)->nullable()->after('chatkit_workflow_id');
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn([
                'integration',
                'chatkit_workflow_id',
                'chatkit_workflow_version',
            ]);
        });
    }
};
