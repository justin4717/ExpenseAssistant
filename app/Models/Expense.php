<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'amount',
        'description',
        'expense_date',
        'payment_method',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the expense.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category of the expense.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the receipt for this expense.
     */
    public function receipt()
    {
        return $this->hasOne(Receipt::class);
    }

    /**
     * Get the items for this expense.
     */
    public function items()
    {
        return $this->hasMany(ExpenseItem::class);
    }
}
