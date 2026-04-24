<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agents')) {
            return;
        }

        if (Schema::hasColumn('agents', 'video_path') && ! Schema::hasColumn('agents', 'youtube_video_id')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->renameColumn('video_path', 'youtube_video_id');
            });

            return;
        }

        if (Schema::hasColumn('agents', 'video_youtube_id') && ! Schema::hasColumn('agents', 'youtube_video_id')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->renameColumn('video_youtube_id', 'youtube_video_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('agents')) {
            return;
        }

        if (Schema::hasColumn('agents', 'youtube_video_id') && ! Schema::hasColumn('agents', 'video_path')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->renameColumn('youtube_video_id', 'video_youtube_id');
            });
        }
    }
};
