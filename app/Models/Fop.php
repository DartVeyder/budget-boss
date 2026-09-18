<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class Fop extends Model
{
    use HasFactory, AsSource, Filterable;

    public function scopeUser(Builder $query)
    {
        return $query->where('user_id', Auth::id());
    }

    protected $fillable = [
        'user_id',
        'finance_bill_id',
        'fop_group_id',
        'name',
        'ipn',
        'ewn',
        'address',
        'phone',

        'director',
        'is_active',

        'transaction_category_id',
        'tax_status',
        'annual_limit',
        'custom_esv',
        'is_esv_exempt',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_esv_exempt' => 'boolean',
        'annual_limit' => 'float',
        'custom_esv' => 'float',
    ];

    /**
     * Get effective annual limit for this FOP (own or group default)
     */
    public function getEffectiveAnnualLimitAttribute(): float
    {
        return (float)($this->annual_limit ?: ($this->fopGroup?->annual_limit ?: 8285700.00));
    }

    /**
     * Get effective monthly ESV for this FOP (own or group default)
     */
    public function getEffectiveMonthlyEsvAttribute(): float
    {
        if ($this->is_esv_exempt) {
            return 0.0;
        }

        return (float)($this->custom_esv ?: ($this->fopGroup?->monthly_esv ?: 1760.00));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function bill()
    {
        return $this->belongsTo(FinanceBill::class, 'finance_bill_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fopGroup()
    {
        return $this->belongsTo(FopGroup::class, 'fop_group_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions()
    {
        return $this->hasMany(FinanceTransaction::class, 'fop_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function quarterDocuments()
    {
        return $this->hasMany(FopQuarterDocument::class, 'fop_id');
    }
}
