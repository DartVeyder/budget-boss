<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class FinanceTransaction extends Model
{
    use HasFactory;
    use AsSource;
    use Filterable;
    use SoftDeletes;

    protected $dates = ['deleted_at'];
    protected $guarded = [];


    public function currency(): BelongsTo{
        return $this->belongsTo(FinanceCurrency::class, 'finance_currency_id');
    }

    public function source(): object{
        return $this->belongsTo(FinanceSource::class);
    }

    public function transactionCategory(): object{
        return $this->belongsTo(FinanceTransactionCategory::class);
    }

    public function paymentMethod(): object{
        return $this->belongsTo(FinancePaymentMethod::class);
    }

    public function transactionType(): BelongsTo{
        return $this->belongsTo(FinanceTransactionType::class, 'finance_transaction_type_id');
    }

    public function user(): object{
        return $this->belongsTo(User::class);
    }
}