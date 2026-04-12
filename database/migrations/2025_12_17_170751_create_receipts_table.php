<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained()->onDelete('cascade');
            $table->string('file_path');// Storage path like 'receipts/user123/receipt1.jpg'
            $table->string('file_type');// 'image/jpeg', 'application/pdf', etc.
            $table->string('original_filename');// Original file name uploaded by user
            $table->enum('ocr_status', ['pending','processing','completed','failed'])->default('pending');
            $table->text('ocr_raw_text')->nullable(); // Raw text from OCR
            $table->timestamp('ocr_processed_at')->nullable(); // when OCR completed

            $table->timestamps();

            $table->index('expense_id');
            $table->index('ocr_status'); // For finding pending OCR jobs
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('receipts');
    }
};
