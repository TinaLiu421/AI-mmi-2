<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentUploadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('document_uploads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id')->nullable();
            $table->string('original_filename');
            $table->string('stored_filename');
            $table->string('file_path');
            $table->string('file_type'); // pdf, docx, jpg, png, etc.
            $table->integer('file_size'); // in bytes
            $table->string('file_hash')->nullable(); // MD5 hash of file content for duplicate detection
            $table->text('extracted_text')->nullable();
            $table->longText('analysis_result')->nullable(); // JSON structured result
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->string('error_message')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('member_id');
            $table->index('file_hash');
            $table->unique(['member_id', 'file_hash']); // Prevent duplicate uploads from same user
            $table->index('file_type');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('document_uploads');
    }
}
