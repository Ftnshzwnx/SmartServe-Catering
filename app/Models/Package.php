<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    protected $fillable = [
        'package_name',
        'price',
        'min_order',
        'image',
        'description',
        'dish_limits',
    ];

    protected $casts = [
        'dish_limits' => 'array',
    ];

    protected $appends = ['addons'];

    protected static $globalActiveAddons = null;

    public function getAddonsAttribute()
    {
        if (self::$globalActiveAddons === null) {
            self::$globalActiveAddons = Addon::where('active', true)->orderBy('addon_name')->get();
        }
        return self::$globalActiveAddons;
    }

    public function dishes(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Dish::class, 'package_dish')->withTimestamps();
    }
}
