<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\Where;
use Orchid\Filters\Types\WhereDateStartEnd;
use Orchid\Filters\Types\WhereMaxMin;
use Orchid\Metrics\Chartable;
use Orchid\Screen\AsSource;

class FinanceTransaction extends Model
{
    use HasFactory;
    use AsSource;
    use Filterable;
    use Chartable;

    protected $guarded = [];

    /**
     * @var array
     */
    protected $allowedFilters = [
        'transaction_type_id'  => Where::class,
        'transaction_category_id'=> Where::class,
        'finance_bill_id' => Where::class,
        'created_at' => WhereDateStartEnd::class,
        'amount' => WhereMaxMin::class
    ];


    protected $allowedSorts = [
        'id',
        'transaction_type_id',
        'transaction_category_id',
        'amount',
        'finance_bill_id',
        'created_at'
    ];


    public function scopeTotalAmount($query)
    {
        return $query->sum(DB::raw('currency_value * amount'));
    }

    public function getAdditionalAttribute(): Collection
    {
        return collect([
            'some_option' => true,
        ]);
    }

    public function getAmountCurrencyAttribute(): float{
        return $this->amount * 2;
    }

    public function bill(){
        return $this->belongsTo(FinanceBill::class , 'finance_bill_id');
    }

    public function category(){
        return $this->belongsTo(FinanceTransactionCategory::class, 'transaction_category_id');
    }

    public function currency(){
        return $this->belongsTo(FinanceCurrency::class, 'finance_currency_id');
    }
    public function invoice(){
        return $this->belongsTo(FinanceInvoice::class, 'finance_invoice_id');
    }

    public function type(){
        return $this->belongsTo(FinanceTransactionType::class);
    }

}
