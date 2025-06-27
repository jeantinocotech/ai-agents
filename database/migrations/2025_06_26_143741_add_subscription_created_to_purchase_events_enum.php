<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the event_type enum to include all event types used in AsaasWebhookController
        DB::statement("ALTER TABLE purchase_events MODIFY COLUMN event_type ENUM(
            'paused',
            'resumed',
            'subscription_created',
            'payment_confirmed',
            'payment_overdue',
            'payment_refunded',
            'subscription_cancelled',
            'subscription_paused',
            'subscription_resumed'
        ) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        DB::statement("ALTER TABLE purchase_events MODIFY COLUMN event_type ENUM(
            'paused',
            'resumed'
        ) NOT NULL");
    }
};