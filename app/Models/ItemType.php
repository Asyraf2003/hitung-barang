<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ItemType extends Model
{
    protected $fillable = [
        'item_id',
        'type_key',
        'label',
        'diameter_mm',
        'balance_kg',
        'meta',
    ];

    protected $casts = [
        'diameter_mm' => 'decimal:2',
        'balance_kg'  => 'decimal:2',
        'meta' => 'array',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
