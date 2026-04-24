<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('type', 8);
            $table->string('title')->nullable();
            $table->longText('body');
            $table->unsignedBigInteger('paired_cv_document_id')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'agent_id', 'type']);
        });

        Schema::table('agent_documents', function (Blueprint $table) {
            $table->foreign('paired_cv_document_id')
                ->references('id')
                ->on('agent_documents')
                ->nullOnDelete();
        });

        Schema::create('agent_document_defaults', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('default_cv_document_id')->nullable()->constrained('agent_documents')->nullOnDelete();
            $table->foreignId('default_jd_document_id')->nullable()->constrained('agent_documents')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'agent_id']);
        });

        if (Schema::hasTable('saved_agent_cvs')) {
            foreach (DB::table('saved_agent_cvs')->get() as $row) {
                $id = DB::table('agent_documents')->insertGetId([
                    'user_id' => $row->user_id,
                    'agent_id' => $row->agent_id,
                    'type' => 'cv',
                    'title' => 'CV importado',
                    'body' => $row->body,
                    'paired_cv_document_id' => null,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);

                DB::table('agent_document_defaults')->updateOrInsert(
                    ['user_id' => $row->user_id, 'agent_id' => $row->agent_id],
                    [
                        'default_cv_document_id' => $id,
                        'default_jd_document_id' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        if (Schema::hasTable('saved_agent_jds')) {
            foreach (DB::table('saved_agent_jds')->get() as $row) {
                $defaultCvId = DB::table('agent_document_defaults')
                    ->where('user_id', $row->user_id)
                    ->where('agent_id', $row->agent_id)
                    ->value('default_cv_document_id');

                $jdId = DB::table('agent_documents')->insertGetId([
                    'user_id' => $row->user_id,
                    'agent_id' => $row->agent_id,
                    'type' => 'jd',
                    'title' => 'Vaga importada',
                    'body' => $row->body,
                    'paired_cv_document_id' => $defaultCvId,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);

                $def = DB::table('agent_document_defaults')
                    ->where('user_id', $row->user_id)
                    ->where('agent_id', $row->agent_id)
                    ->first();

                if ($def) {
                    DB::table('agent_document_defaults')
                        ->where('id', $def->id)
                        ->update([
                            'default_jd_document_id' => $jdId,
                            'updated_at' => now(),
                        ]);
                } else {
                    DB::table('agent_document_defaults')->insert([
                        'user_id' => $row->user_id,
                        'agent_id' => $row->agent_id,
                        'default_cv_document_id' => $defaultCvId,
                        'default_jd_document_id' => $jdId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        Schema::dropIfExists('saved_agent_jds');
        Schema::dropIfExists('saved_agent_cvs');
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_document_defaults');
        Schema::dropIfExists('agent_documents');

        Schema::create('saved_agent_cvs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->longText('body');
            $table->timestamps();
            $table->unique(['user_id', 'agent_id']);
        });

        Schema::create('saved_agent_jds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->longText('body');
            $table->timestamps();
            $table->unique(['user_id', 'agent_id']);
        });
    }
};
