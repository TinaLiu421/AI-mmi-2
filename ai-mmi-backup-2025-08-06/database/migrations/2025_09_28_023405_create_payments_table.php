<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('member_id')->nullable();
            $t->string('stripe_customer_id')->nullable();
            $t->string('stripe_session_id')->nullable();
            $t->string('stripe_subscription_id')->nullable();
            $t->string('product_id')->nullable();  // Stripe product
            $t->string('price_id')->nullable();    // Stripe price (套餐)
            $t->integer('amount_total')->nullable(); // 分为单位
            $t->string('currency', 10)->nullable();  // 'usd'
            $t->string('status')->default('pending'); // pending|paid|failed|canceled
            $t->json('raw_payload')->nullable();
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
}
