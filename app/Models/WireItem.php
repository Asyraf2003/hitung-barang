<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WireItem extends Model
{
    protected $fillable = [
        'diameter_mm',
        'name',
        'meta',
    ];

    protected $casts = [
        'diameter_mm' => 'decimal:2',
        'meta' => 'array',
    ];

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
