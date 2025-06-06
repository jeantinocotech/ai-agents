<?php
		
		use Illuminate\Database\Migrations\Migration;
		use Illuminate\Database\Schema\Blueprint;
		use Illuminate\Support\Facades\Schema;
		
		return new class extends Migration
		{
		    public function up(): void
		    {
		        Schema::create('agents', function (Blueprint $table) {
		            $table->id();
		            $table->string('name');
		            $table->text('description')->nullable();
		            $table->string('image_path')->nullable();
		            $table->string('video_youtube_id')->nullable();
			        $table->string('api_key')->nullable();
			        $table->string('model_type');
		            $table->timestamps();
		        });
		    }
		
		    public function down(): void
		    {
		        Schema::dropIfExists('agents');
		    }
		};


