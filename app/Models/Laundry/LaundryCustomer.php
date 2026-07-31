<?php

namespace App\Models\Laundry;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaundryCustomer extends Model
{
    use BelongsToTenant;

    protected $table = 'laundry_customers';

    protected $fillable = [
        'tenant_id', 'name', 'phone', 'email', 'address', 'member_status', 'loyalty_points',
    ];

    protected $casts = [
        'loyalty_points' => 'integer',
    ];

    public function isVip(): bool
    {
        return $this->member_status === 'vip';
    }

    public function orders(): HasMany
    {
        return $this->hasMany(LaundryOrder::class, 'customer_id');
    }
}
