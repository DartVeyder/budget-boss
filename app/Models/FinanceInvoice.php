<?php

namespace App\Models;

use App\Services\Finance\Act\UkrainianNumberToWords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;
use Orchid\Attachment\Attachable;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class FinanceInvoice extends Model
{
    use HasFactory, AsSource, Filterable, Attachable;

    protected $guarded = [];

    protected $casts = [
        'items_data' => 'array',
        'invoice_date' => 'date',
        'due_date' => 'date',
        'contract_date' => 'date',
        'total' => 'float',
        'amount_paid' => 'float',
    ];

    public function scopeUser(Builder $query)
    {
        return $query->where('user_id', Auth::id());
    }

    public function getTranslatedStatusAttribute()
    {
        $status = [
            'not_paid' => 'не оплачено',
            'not paid' => 'не оплачено',
            'part paid' => 'оплачено частково',
            'paid' => 'оплачено',
            'cancelled' => 'скасовано',
        ];

        return $status[$this->status] ?? $this->status;
    }

    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'not_paid' => '<span class="badge bg-warning text-dark">Не оплачено</span>',
            'not paid' => '<span class="badge bg-warning text-dark">Не оплачено</span>',
            'part paid' => '<span class="badge bg-info">Частково</span>',
            'paid' => '<span class="badge bg-success">Оплачено</span>',
            'cancelled' => '<span class="badge bg-danger">Скасовано</span>',
        ];

        return $badges[$this->status] ?? "<span class=\"badge bg-light text-dark\">{$this->status}</span>";
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function fop(): BelongsTo
    {
        return $this->belongsTo(Fop::class, 'fop_id');
    }

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(CustomerCounterparty::class, 'counterparty_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(FinanceCurrency::class, 'finance_currency_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FinanceTransaction::class);
    }

    public function act(): HasOne
    {
        return $this->hasOne(Act::class, 'finance_invoice_id');
    }

    public function getTotalInWordsAttribute(): string
    {
        return UkrainianNumberToWords::convert($this->total);
    }
}
