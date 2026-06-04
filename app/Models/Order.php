<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'package_name',
        'delivery_address',
        'total_price',
        'package_image',
        'payment_proof',
        'status',
        'delivery_date',
        'delivery_time',
        'qr_code_path',
        'cancelled_by',
        'cancelled_at',
        'admin_note',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
}
