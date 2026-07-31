<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\LogsAllActivity;

class Menu extends Model
{
    use HasUuids, BelongsToTenant, LogsAllActivity;

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

    /** RESEP (modul HPP): bahan + gramasi per porsi. */
    public function menuIngredients()
    {
        return $this->hasMany(\App\Models\Fnb\MenuIngredient::class);
    }

    /** Bahan baku yang dipakai menu ini (via resep). */
    public function ingredients()
    {
        return $this->belongsToMany(\App\Models\Fnb\Ingredient::class, 'menu_ingredients')
            ->withPivot('quantity');
    }
}
