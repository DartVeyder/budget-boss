<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class FopGroup extends Model
{
    use HasFactory, AsSource, Filterable;

    protected $fillable = [
        'name',
        'annual_limit',
        'monthly_esv',
    ];

    protected $casts = [
        'annual_limit' => 'float',
        'monthly_esv' => 'float',
    ];

    public function taxRates()
    {
        return $this->belongsToMany(TaxRate::class, 'fop_group_tax_rate', 'fop_group_id', 'tax_rate_id');
    }

    public function fops()
    {
        return $this->hasMany(Fop::class);
    }
}
