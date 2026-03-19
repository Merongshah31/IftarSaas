<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Scopes\TenantScope;

class IftarDay extends Model
{
    use HasFactory;

    //table name 
    protected $table = 'iftar_days';

    protected $fillable = [
        'masjid_id',
        'tarikh',
        'kapasiti_max',
        'jumlah_daftar',
        'status',
        'notes'
    ];

    //casts
    protected $casts = [
        'tarikh' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    //relationship
    public function masjid(): BelongsTo
    {
        return $this->belongsTo(Masjid::class, 'masjid_id');
    }

    //Relationship has many participants
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class, 'iftar_day_id');
    }
    //relationship has many sponsorships
    public function sponsorships(): HasMany
    {
        return $this->hasMany(Sponsorship::class, 'iftar_day_id');
    }
}
