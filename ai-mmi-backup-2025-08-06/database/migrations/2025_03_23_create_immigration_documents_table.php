<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('immigration_documents', function (Blueprint $table) {
            $table->id();
            $table->string('country')->index(); // 'australia', 'nz'
            $table->string('source_url')->index();
            $table->string('title');
            $table->longText('content');
            $table->longText('content_clean'); // Cleaned text for search
            $table->string('section', 255)->nullable()->index(); // e.g., 'business-visa', 'work-visa'
            $table->text('keywords')->nullable(); // Extracted keywords
            $table->integer('word_count')->default(0);
            $table->timestamps();
            
            // Full-text search index
            $table->fullText(['title', 'content_clean']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('immigration_documents');
    }
};
