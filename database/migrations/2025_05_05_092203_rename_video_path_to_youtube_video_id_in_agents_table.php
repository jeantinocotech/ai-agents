<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->renameColumn('video_path', 'youtube_video_id');
        });
    }

    public function down()
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->renameColumn('youtube_video_id', 'video_path');
        });
    }
};
