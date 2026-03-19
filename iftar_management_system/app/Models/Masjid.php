<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;  // ← Add this
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Masjid extends Model
{
    use HasFactory;  
    
    protected $table = 'masjid';

    protected $fillable = [
        'nama_masjid',
        'alamat',
        'negeri',
        'contact_phone',
        'logo_url',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function iftarDays(): HasMany
    {
        return $this->hasMany(IftarDay::class, 'masjid_id');
    }

    public function sponsorships(): HasMany
    {
        return $this->hasMany(Sponsorship::class, 'masjid_id');
    }
}
