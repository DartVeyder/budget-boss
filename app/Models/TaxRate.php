<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Screen\AsSource;

class TaxRate extends Model
{
    use HasFactory, AsSource;

    protected $fillable = ['name', 'value'];

    public function fopGroups()
    {
        return $this->belongsToMany(FopGroup::class, 'fop_group_tax_rate', 'tax_rate_id', 'fop_group_id');
    }
}
