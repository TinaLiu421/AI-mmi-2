<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('spotlight_queue')) {
            return;
        }

        Schema::create('spotlight_queue', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('member_id')->index();
            $table->unsignedInteger('posts_id')->index();

            // pending_payment = checkout started, not yet paid
            // queued          = paid, waiting for a slot
            // active          = currently in spotlight
            // expired         = spotlight period ended
            // cancelled       = admin cancelled / refunded
            $table->string('status', 20)->default('pending_payment')->index();

            // Stripe checkout session id (for idempotency)
            $table->string('stripe_session_id', 200)->nullable()->unique();

            // When this entry was paid for (webhook arrival time → tie-breaking)
            $table->timestamp('paid_at')->nullable();

            // Scheduled / actual spotlight window
            $table->timestamp('scheduled_start')->nullable()->index();
            $table->timestamp('scheduled_end')->nullable()->index();

            // Actual activation / expiry timestamps (filled when status changes)
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expired_at')->nullable();

            // Queue position at time of payment (1-indexed among queued+active)
            $table->unsignedSmallInteger('queue_position')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spotlight_queue');
    }
};
