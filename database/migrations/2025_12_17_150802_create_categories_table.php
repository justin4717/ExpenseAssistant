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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();// Primary key
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Foreign key to users table
            $table->string('name'); // Category name
            $table->enum('type', ['expense', 'income'])->default('expense'); // Category type
            $table->string('color', 7)->nullable(); // Optional color code
            $table->string('icon')->nullable();// Optional icon name
            $table->timestamps();

            //Index for faster lookups
            $table->index('user_id');//often Filter by user_id
            $table->index('type');//often filter by Type
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('categories');
    }
};
