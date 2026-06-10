<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        'package_id',
        'package_name',
        'quantity',
        'price',
        'total_price',
        'selected_addons',
        'addon_cost',
        'selected_dishes',
    ];

    protected $casts = [
        'selected_addons' => 'array',
        'selected_dishes' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'package_id');
    }
}
