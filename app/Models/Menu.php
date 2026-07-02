<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\Concerns\BelongsToTenant;

class Menu extends Model
{
    use HasUuids, BelongsToTenant;

    protected $fillable = ['uuid', 'tenant_id', 'category_id', 'name', 'description', 'price', 'discount_percent', 'image', 'is_available'];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function addons()
    {
        return $this->hasMany(MenuAddon::class);
    }

    public function activeAddons()
    {
        return $this->hasMany(MenuAddon::class)->where('is_active', true);
    }
}
