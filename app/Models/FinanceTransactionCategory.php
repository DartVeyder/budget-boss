<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class FinanceTransactionCategory extends Model
{
    use HasFactory;
    use AsSource;
    use Filterable;
    protected $guarded = [];

    public function scopeIncome(Builder $query)
    {
        return $query->where('transaction_type_id', 2)->where('user_id',Auth::user()->id);
    }

    public function scopeExpenses(Builder $query)
    {
        return $query->where('transaction_type_id', 1)->where('user_id',Auth::user()->id);
    }

    public function mccs()
    {
        return $this->belongsToMany(FinanceTransactionMcc::class, 'finance_transaction_category_mcc', 'transaction_category_id', 'transaction_mcc_id');
    }
}
