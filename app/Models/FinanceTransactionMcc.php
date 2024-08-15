<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Screen\AsSource;

class FinanceTransactionMcc extends Model
{
    use HasFactory;
    use AsSource;

    protected $guarded = [];
    public function categories()
    {
        return $this->belongsToMany(FinanceTransactionCategory::class, 'finance_transaction_category_mcc', 'transaction_mcc_id', 'transaction_category_id');
    }
}
