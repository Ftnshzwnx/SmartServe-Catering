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
    ];

    public function addons(): HasMany
    {
        return $this->hasMany(PackageAddon::class, 'package_id');
    }
}
