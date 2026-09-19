<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class Customer extends Model
{
    use HasFactory;
    use AsSource;
    use Filterable;


    protected $guarded = [];

    protected $casts = [
        'is_fop' => 'boolean',
        'is_single_tax' => 'boolean',
        'is_vat_payer' => 'boolean',
    ];

    public function scopeUser(Builder $query)
    {
        return $query->where('user_id',Auth::user()->id);
    }

    public function fop()
    {
        return $this->belongsTo(Fop::class);
    }

    public function counterparties()
    {
        return $this->hasMany(CustomerCounterparty::class)->orderBy('name');
    }

    public function acts()
    {
        return $this->hasMany(Act::class)->latest('act_date');
    }
}
