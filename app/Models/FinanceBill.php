<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class FinanceBill extends Model
{
    use HasFactory;
    use AsSource;
    use Filterable;

    protected $guarded = [];

    public function scopeUser(Builder $query)
    {
        return $query->where('user_id',Auth::user()->id);
    }

    public function transactions(){
        return $this->hasMany(FinanceTransaction::class,'finance_bill_id');
    }


}
