<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Item extends Model
{
    protected $fillable = [
        'code',
        'name',
        'unit',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function types(): HasMany
    {
        return $this->hasMany(ItemType::class);
    }
}
