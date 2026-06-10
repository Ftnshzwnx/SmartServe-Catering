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
        'delivery_zone',
        'delivery_fee',
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
        'notes',
        'promo_code_id',
        'discount_amount',
        'refund_bank_name',
        'refund_account_number',
        'refund_account_name',
        'reschedule_date',
        'reschedule_time',
        'reschedule_status',
        'is_custom_proposal',
    ];

    protected $casts = [
        'is_custom_proposal' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class, 'promo_code_id');
    }

    public function review(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Review::class, 'order_id');
    }
}
