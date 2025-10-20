<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('app_chunks', function (Blueprint $table) {
      $table->bigIncrements('id');
      $table->string('source_type')->nullable();   // e.g. 'file','url','db'
      $table->string('source_id')->nullable();     // e.g. file path / doc id
      $table->unsignedInteger('chunk_index')->default(0);
      $table->text('content');                     // 纯文本切片
      $table->json('meta')->nullable();            // 其他元信息（标题、标签等）
      $table->timestamps();
      $table->index(['source_type','source_id']);
    });
  }
  public function down(): void {
    Schema::dropIfExists('app_chunks');
  }
};
