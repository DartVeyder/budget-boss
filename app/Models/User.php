<?php

namespace App\Models;

use Orchid\Filters\Types\Like;
use Orchid\Filters\Types\Where;
use Orchid\Filters\Types\WhereDateStartEnd;
use Orchid\Platform\Models\User as Authenticatable;

class User extends Authenticatable
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'permissions',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'permissions'          => 'array',
        'email_verified_at'    => 'datetime',
    ];

    /**
     * The attributes for which you can use filters in url.
     *
     * @var array
     */
    protected $allowedFilters = [
           'id'         => Where::class,
           'name'       => Like::class,
           'email'      => Like::class,
           'updated_at' => WhereDateStartEnd::class,
           'created_at' => WhereDateStartEnd::class,
    ];

    /**
     * The attributes for which can use sort in url.
     *
     * @var array
     */
    protected $allowedSorts = [
        'id',
        'name',
        'email',
        'updated_at',
        'created_at',
    ];

    public function bills(): object{
        return $this->hasMany(FinanceBill::class);
    }
    public function transactions(): object{
        return $this->hasMany(FinanceTransaction::class);
    }
    public function customers(): object{
        return $this->hasMany(Customer::class);
    }
    public function invoices(): object{
        return $this->hasMany(FinanceInvoice::class);
    }
    public function setting(): object{
        return $this->hasOne(UserSetting::class);
    }
    public function budgetShares(): object{
        return $this->hasMany(BudgetShare::class, 'user_id');
    }
    public function inverseBudgetShares(): object{
        return $this->hasMany(BudgetShare::class, 'partner_id');
    }

    /**
     * Get all user IDs participating in the shared budget (self + partners).
     *
     * @return int[]
     */
    public function getSharedUserIds(): array
    {
        $partnerIds1 = BudgetShare::where('user_id', $this->id)->pluck('partner_id')->all();
        $partnerIds2 = BudgetShare::where('partner_id', $this->id)->pluck('user_id')->all();

        return array_values(array_unique(array_merge([$this->id], $partnerIds1, $partnerIds2)));
    }

    /**
     * Get all users in the shared budget (self + partners).
     */
    public function getSharedUsers(): \Illuminate\Database\Eloquent\Collection
    {
        return self::whereIn('id', $this->getSharedUserIds())->get();
    }

    /**
     * Check if user is sharing budget with at least one other user.
     */
    public function isSharingBudget(): bool
    {
        return count($this->getSharedUserIds()) > 1;
    }

    /**
     * Get share record between this user and another user.
     */
    public function getBudgetShareWith(int $partnerId): ?BudgetShare
    {
        return BudgetShare::where(function ($q) use ($partnerId) {
            $q->where('user_id', $this->id)->where('partner_id', $partnerId);
        })->orWhere(function ($q) use ($partnerId) {
            $q->where('user_id', $partnerId)->where('partner_id', $this->id);
        })->first();
    }

    /**
     * Check if this user has editor rights in shared budget with partner.
     */
    public function canEditSharedBudgetWith(int $partnerId): bool
    {
        if ($this->id === $partnerId) {
            return true;
        }

        $share = $this->getBudgetShareWith($partnerId);
        if (!$share) {
            return false;
        }

        return $share->role === BudgetShare::ROLE_EDITOR && $share->can_create_transactions;
    }
}

