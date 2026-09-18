<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'act_id',
        'name',
        'unit',
        'quantity',
        'price',
        'amount',
    ];

    protected $casts = [
        'quantity' => 'float',
        'price' => 'float',
        'amount' => 'float',
    ];

    protected static function booted()
    {
        static::saving(function (ActItem $item) {
            if (!$item->amount || $item->isDirty(['quantity', 'price'])) {
                $item->amount = round((float)$item->quantity * (float)$item->price, 2);
            }
        });
    }

    public function act(): BelongsTo
    {
        return $this->belongsTo(Act::class);
    }
}
