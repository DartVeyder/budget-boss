<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orchid\Filters\Filterable;
use Orchid\Metrics\Chartable;
use Orchid\Screen\AsSource;

class FinanceTransaction extends Model
{
    use HasFactory;
    use AsSource;
    use Filterable;
    use SoftDeletes;
    use Chartable;

    protected $guarded = [];

    public function bill(){
        return $this->belongsTo(FinanceBill::class , 'finance_bill_id');
    }

    public function category(){
        return $this->belongsTo(FinanceTransactionCategory::class, 'transaction_category_id');
    }

    public function type(){
        return $this->belongsTo(FinanceTransactionType::class);
    }
}
