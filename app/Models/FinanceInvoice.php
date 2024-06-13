<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class FinanceInvoice extends Model
{
    use HasFactory;
    use AsSource;
    use Filterable;

    protected $guarded = [];

    public function scopeUser(Builder $query)
    {
        return $query->where('user_id',Auth::user()->id);
    }

    public function getTranslatedStatusAttribute()
    {
        $status = [
            'not_paid' => 'не оплачено',
            'paid' => 'оплачено',
            'cancelled' => 'скасовано'
        ];

        return  $status[$this->status] ?? $this->status;
    }

    public function customer(){
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function currency(){
        return $this->belongsTo(FinanceCurrency::class, 'finance_currency_id');
    }

    public function transactions(){
        return $this->hasMany(FinanceTransaction::class);
    }


}
