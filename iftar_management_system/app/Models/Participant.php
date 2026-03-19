<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Services\TenantContext;

class Participant extends Model
{
    use HasFactory;
    
    protected $table = 'participants';

    protected $fillable = [
        'masjid_id',
        'iftar_day_id',
        'nama',
        'no_telefon',
        'bil_pax',
        'checkin_status',
        'reminder_sent',
        'is_cancelled',
        'cancelled_at',
        'cancellation_count',
        'notes',
    ];

    protected $casts = [
        'reminder_sent' => 'boolean',
        'is_cancelled' => 'boolean',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Scope participants directly by masjid_id.
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (!TenantContext::hasTenant()) {
                return;
            }

            $tenantId = TenantContext::getTenantId();
            $builder->where($builder->getModel()->getTable() . '.masjid_id', $tenantId);
        });
    }

    public function iftarDay(): BelongsTo
    {
        return $this->belongsTo(IftarDay::class, 'iftar_day_id');
    }
}
