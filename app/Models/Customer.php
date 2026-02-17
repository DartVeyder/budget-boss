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
    ];

    public function scopeUser(Builder $query)
    {
        return $query->where('user_id',Auth::user()->id);
    }

}
