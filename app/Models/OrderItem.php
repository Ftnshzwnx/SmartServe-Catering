<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'package_id',
        'quantity',
        'price',
        'subtotal',
        'selected_dishes',
        'selected_addons',
        'addon_cost',
    ];

    protected $casts = [
        'selected_dishes' => 'array',
        'selected_addons' => 'array',
        'addon_cost' => 'float',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'package_id');
    }
}
