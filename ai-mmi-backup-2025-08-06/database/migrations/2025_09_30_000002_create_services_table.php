<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('services', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique(); // ai_migration_qna 等
            $t->string('name');
            $t->string('category')->default('migration'); // migration / education / support / payment
            $t->string('unit')->nullable(); // questions / minutes / applications / checks / unlimited
            $t->string('description', 1024)->nullable();
            $t->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('services'); }
};
