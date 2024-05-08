<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class FinanceTransactionCategory extends Model
{
    use HasFactory;
    use AsSource;
    use Filterable;

    protected $guarded = [];

    public function transactions(): object{
        return $this->hasMany(FinanceTransaction::class);
    }
}
