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
        Schema::create('expense_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained()->onDelete('cascade');
            $table->foreignId('receipt_id')->nullable()->constrained()->onDelete('set null'); // Link to receipt if applicable
            $table->string('item_name'); // eg. 'Lunch', 'Taxi fare'
            $table->decimal('quantity', 8, 2)->default(1); // eg. 1.5 kg, 2 hours, 2 items
            $table->decimal('unit_price', 10, 2);// price per unit
            $table->decimal('total_price', 10, 2); // quantity * unit_price

            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null'); //Optional item-level category

            $table->text('notes')->nullable(); // optional notes about the item
            $table->timestamps();

            $table->index('expense_id');
            $table->index('receipt_id');
            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('expense_items');
    }
};
