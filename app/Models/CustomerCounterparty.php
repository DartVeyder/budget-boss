<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class CustomerCounterparty extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $fillable = [
        'customer_id',
        'user_id',
        'name',
        'ipn',
        'iban',
        'bank_name',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeUser(Builder $query): Builder
    {
        return $query->where('user_id', Auth::id());
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FinanceTransaction::class, 'counterparty_id');
    }

    public function getFullTitleAttribute(): string
    {
        $parts = [$this->name];
        if ($this->ipn) {
            $parts[] = "ІПН: {$this->ipn}";
        }
        if ($this->iban) {
            $parts[] = "IBAN: " . substr($this->iban, 0, 8) . '...';
        }

        return implode(' • ', $parts);
    }
}
