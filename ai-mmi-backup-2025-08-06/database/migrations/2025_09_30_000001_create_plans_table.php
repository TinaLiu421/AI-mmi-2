<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('plans', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique(); // free, all_ai, hybrid, premium, vip
            $t->string('name');
            $t->unsignedInteger('duration_months')->nullable(); // 例如 6；null=不限期
            $t->decimal('price_usd', 10, 2)->default(0);
            $t->string('business_domain')->default('combined'); // migration / education / combined
            $t->boolean('is_active')->default(true);
            $t->string('stripe_price_id')->nullable();
            $t->string('description', 1024)->nullable();
            $t->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('plans'); }
};
