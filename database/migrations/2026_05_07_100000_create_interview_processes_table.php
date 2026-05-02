<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('jd_document_id')->constrained('agent_documents')->cascadeOnDelete();
            $table->string('outcome', 24);
            $table->timestamps();

            $table->unique(['user_id', 'jd_document_id']);
            $table->index('outcome');
        });

        $pairs = DB::table('interview_preparations')
            ->select(['user_id', 'jd_document_id'])
            ->distinct()
            ->get();

        foreach ($pairs as $row) {
            $uid = (int) $row->user_id;
            $jid = (int) $row->jd_document_id;

            $terminated = DB::table('interview_preparations')
                ->where('user_id', $uid)
                ->where('jd_document_id', $jid)
                ->whereIn('status', ['rejected', 'withdrawn'])
                ->exists();

            DB::table('interview_processes')->insert([
                'user_id' => $uid,
                'jd_document_id' => $jid,
                'outcome' => $terminated ? 'did_not_proceed' : 'ongoing',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_processes');
    }
};
