<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'expense_id',
        'file_path',
        'file_type',
        'original_filename',
        'ocr_status',
        'ocr_raw_text',
        'ocr_processed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'ocr_processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the expense that owns the receipt.
     */
    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    /**
     * Get the items extracted from this receipt.
     */
    public function items()
    {
        return $this->hasMany(ExpenseItem::class);
    }
}
