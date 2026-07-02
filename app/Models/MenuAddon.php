<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\LogsAllActivity;

class MenuAddon extends Model
{
    use BelongsToTenant, LogsAllActivity;

    protected $table = 'menu_addons';

    protected $fillable = ['tenant_id', 'menu_id', 'name', 'price', 'is_active'];

    protected $casts = [
        'price'     => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
}
