<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMemberSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('member_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('member_id'); // Link to member table (match member.id type)
            $table->unsignedBigInteger('subscription_plan_id'); // Link to subscription_plans
            $table->unsignedBigInteger('payment_id')->nullable(); // Link to payments table

            // Stripe references
            $table->string('stripe_subscription_id')->nullable()->unique();
            $table->string('stripe_customer_id')->nullable();

            // Subscription status
            $table->enum('status', ['active', 'trialing', 'past_due', 'canceled', 'incomplete', 'incomplete_expired', 'unpaid', 'paused'])->default('active');

            // Dates
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('canceled_at')->nullable();

            // Usage tracking (for limits)
            $table->integer('migration_questions_used')->default(0);
            $table->integer('education_questions_used')->default(0);
            $table->decimal('human_agent_hours_used', 5, 2)->default(0);
            $table->integer('program_applications_used')->default(0);

            // Metadata
            $table->json('metadata')->nullable(); // For any additional tracking

            $table->timestamps();

            // Foreign keys
            $table->foreign('member_id')->references('id')->on('member')->onDelete('cascade');
            $table->foreign('subscription_plan_id')->references('id')->on('subscription_plans')->onDelete('restrict');
            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('set null');

            // Indexes
            $table->index(['member_id', 'status']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('member_subscriptions');
    }
}