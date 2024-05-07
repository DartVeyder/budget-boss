<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orchid\Filters\Filterable;
use Orchid\Metrics\Chartable;
use Orchid\Screen\AsSource;

class FinanceTransactions extends Model
{
    use HasFactory;
    use AsSource;
    use Filterable;
    use SoftDeletes;

    protected $dates = ['deleted_at'];
    protected $guarded = [];


    public function currency(){
        return $this->belongsTo(FinanceCurrencies::class);
    }

    public function source(): object{
        return $this->belongsTo(FinanceSources::class);
    }

    public function transactionCategory(): object{
        return $this->belongsTo(FinanceTransactionCategories::class);
    }

    public function paymentMethod(): object{
        return $this->belongsTo(FinancePaymentMethods::class);
    }

    public function transactionType(): object{
        return $this->belongsTo(FinanceTransactionTypes::class);
    }

    public function user(): object{
        return $this->belongsTo(User::class);
    }
}