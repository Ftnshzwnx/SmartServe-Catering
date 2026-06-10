<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Addon extends Model
{
    protected $table = 'addons';

    protected $fillable = [
        'addon_name',
        'price_per_pax',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'price_per_pax' => 'decimal:2',
    ];
}
