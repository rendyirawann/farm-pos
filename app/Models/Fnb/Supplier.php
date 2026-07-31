<?php

namespace App\Models\Fnb;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** Pemasok bahan baku (F&B). */
class Supplier extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'name', 'contact_person', 'phone', 'address'];

    public function batches()
    {
        return $this->hasMany(IngredientBatch::class);
    }
}
