<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class UserSetting extends Model
{
    use HasFactory;
    use AsSource;
    use Filterable;
    protected $guarded = [];
    protected $primaryKey = 'user_id';
    public $timestamps = false;
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
