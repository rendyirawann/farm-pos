<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Opsi situs global key-value (Superadmin).
 */
class SiteOption extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    public static function get(string $key, $default = null)
    {
        $row = static::find($key);
        return $row ? $row->value : $default;
    }

    public static function set(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => (string) $value]);
    }
}
