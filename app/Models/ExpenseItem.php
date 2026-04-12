<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'expense_id',
        'receipt_id',
        'item_name',
        'quantity',
        'unit_price',
        'total_price',
        'category_id',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the expense that owns the item.
     */
    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    /**
     * Get the receipt that this item came from.
     */
    public function receipt()
    {
        return $this->belongsTo(Receipt::class);
    }

    /**
     * Get the category of the item.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
