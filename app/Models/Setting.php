<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\LogsAllActivity;

class Setting extends Model
{
    use BelongsToTenant, LogsAllActivity;

    protected $table = 'settings';
    protected $fillable = [
        'tenant_id', 'store_name', 'address', 'phone', 'tax_rate', 'printer_method', 'paper_width',
        'receipt_header', 'receipt_footer', 'receipt_show_address', 'receipt_show_phone',
    ];

    protected $casts = [
        'receipt_show_address' => 'boolean',
        'receipt_show_phone'   => 'boolean',
    ];
}
