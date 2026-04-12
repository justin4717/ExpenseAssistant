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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Foreign key to users table
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null'); // Foreign key to categories table
            $table->decimal('amount',10,2);// 10 digits total, 2 after decimal
            $table->text('description')->nullable();// Optional description
            $table->date('expense_date');// Date of expense occured
            $table->enum('payment_method', ['cash', 'card', 'paypal','bank_transfer','other'])->default('cash'); // Payment method
            $table->text('notes')->nullable();
            $table->timestamps();

            //Indexes for common queries
            $table->index('user_id');
            $table->index('category_id');
            $table->index('expense_date'); // For date range queries
            $table->index(['user_id', 'expense_date']); // Compound index for user's expenses by date
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('expenses');
    }
};
