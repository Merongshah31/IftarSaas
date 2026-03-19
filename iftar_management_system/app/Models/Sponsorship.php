<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Scopes\TenantScope;


class Sponsorship extends Model
{
    use HasFactory;

    //name table
    protected $table = 'sponsorships';
    //fillable fields
    protected $fillable = [
        'iftar_day_id',
        'masjid_id',
        'nama_sponsor',
        'phone',
        'jumlah_tajaan',
        'jenis_tajaan',
        'sponsor_coverage',
        'payment_status',
        'notes',
    ];
    //casts
    protected $casts = [
        'jumlah_tajaan' => 'decimal:2',
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

    public function iftarDay(): BelongsTo
    {
        return $this->belongsTo(IftarDay::class, 'iftar_day_id');
    }

}
