<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

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
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        // Revert back to original enum values
        DB::statement("ALTER TABLE purchase_events MODIFY COLUMN event_type ENUM(
            'paused',
            'resumed'
        ) NOT NULL");
    }
};
