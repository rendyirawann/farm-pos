<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class Category extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'name', 'slug'];

    // Relasi ke Menu: Satu kategori punya banyak menu
    public function menus()
    {
        return $this->hasMany(Menu::class);
    }
}
