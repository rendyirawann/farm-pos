<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Founder/tim inti untuk halaman "Tentang Kami". Foto dikelola Superadmin. */
class Founder extends Model
{
    protected $fillable = ['name', 'position', 'bio', 'photo', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    public function photoUrl(): ?string
    {
        return $this->photo ? asset('storage/' . $this->photo) : null;
    }
}
