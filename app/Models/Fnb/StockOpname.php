<?php

namespace App\Models\Fnb;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** Sesi stok opname (sistem vs fisik). */
class StockOpname extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'user_id', 'date', 'notes'];

    protected $casts = ['date' => 'date'];

    public function details()
    {
        return $this->hasMany(StockOpnameDetail::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
