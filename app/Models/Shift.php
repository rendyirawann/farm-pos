<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str; // Jangan lupa import ini
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\LogsAllActivity;

class Shift extends Model
{
    use BelongsToTenant, LogsAllActivity;

    // Tambahkan 'uuid' di dalam fillable
    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'start_time',
        'end_time',
        'starting_cash',
        'cash_sales',
        'expense_total',
        'expected_cash',
        'actual_cash',
        'difference',
        'status'
    ];

    // Boot function untuk generate UUID otomatis (Sama persis seperti di Sale.php)
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
