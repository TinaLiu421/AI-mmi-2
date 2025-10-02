<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('subscriptions', function (Blueprint $t) {
            $t->id();

            // 用 signed int 对齐 app_member.id（int(11)）
            $t->integer('member_id'); 
            $t->foreign('member_id')->references('id')->on('member')->cascadeOnDelete();

            $t->foreignId('plan_id')->constrained('plans');

            $t->string('status')->default('active');
            $t->timestamp('started_at')->nullable();
            $t->timestamp('ends_at')->nullable();

            $t->string('currency', 10)->default('USD');
            $t->decimal('amount_usd', 10, 2)->default(0);
            $t->string('stripe_customer_id')->nullable();
            $t->string('stripe_subscription_id')->nullable();

            $t->json('meta')->nullable();
            $t->timestamps();
        });

    }
    public function down() { Schema::dropIfExists('subscriptions'); }
};
