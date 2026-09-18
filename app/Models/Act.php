<?php

namespace App\Models;

use App\Services\Finance\Act\UkrainianNumberToWords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class Act extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $fillable = [
        'user_id',
        'fop_id',
        'customer_id',
        'counterparty_id',
        'finance_invoice_id',
        'finance_transaction_id',
        'act_number',
        'act_date',
        'contract_number',
        'contract_date',
        'total_amount',
        'currency_code',
        'status',
        'notes',
    ];

    protected $casts = [
        'act_date' => 'date',
        'contract_date' => 'date',
        'total_amount' => 'float',
    ];

    public function scopeUser(Builder $query): Builder
    {
        return $query->where('user_id', Auth::id());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fop(): BelongsTo
    {
        return $this->belongsTo(Fop::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(CustomerCounterparty::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(FinanceInvoice::class, 'finance_invoice_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FinanceTransaction::class, 'finance_transaction_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ActItem::class);
    }

    public function getTotalAmountInWordsAttribute(): string
    {
        return UkrainianNumberToWords::convert($this->total_amount);
    }

    public function getTranslatedStatusAttribute(): string
    {
        $statuses = [
            'draft' => 'Чернетка',
            'sent' => 'Надіслано клієнту',
            'signed' => 'Підписано',
            'cancelled' => 'Скасовано',
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'draft' => '<span class="badge bg-secondary">Чернетка</span>',
            'sent' => '<span class="badge bg-warning text-dark">Надіслано</span>',
            'signed' => '<span class="badge bg-success">Підписано</span>',
            'cancelled' => '<span class="badge bg-danger">Скасовано</span>',
        ];

        return $badges[$this->status] ?? "<span class=\"badge bg-light text-dark\">{$this->status}</span>";
    }
}
