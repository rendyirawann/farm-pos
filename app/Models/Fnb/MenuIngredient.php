<?php

namespace App\Models\Fnb;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** RESEP: bahan + gramasi untuk 1 porsi menu. */
class MenuIngredient extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'menu_id', 'ingredient_id', 'quantity'];

    protected $casts = ['quantity' => 'decimal:2'];

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function menu()
    {
        return $this->belongsTo(\App\Models\Menu::class);
    }
}
