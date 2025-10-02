<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('plan_entitlements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $t->foreignId('service_id')->constrained('services')->cascadeOnDelete();

            // null=无限；否则为数量（如 5 questions、120 minutes、1 check）
            $t->unsignedInteger('quota')->nullable();

            // 额度周期（可选）：null=随订阅期；否则为天数（例如每30天刷新）
            $t->unsignedInteger('period_days')->nullable();

            // 某些服务的价格覆盖（如：education_application $100/次）
            $t->decimal('price_override_usd', 10, 2)->nullable();

            $t->string('notes', 1024)->nullable();
            $t->timestamps();

            $t->unique(['plan_id','service_id']);
        });
    }
    public function down() { Schema::dropIfExists('plan_entitlements'); }
};
