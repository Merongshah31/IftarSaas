<?php


namespace App\Services;

use App\Models\Participant;
use App\Models\IftarDay;
use Illuminate\Support\Facades\DB;
use Exception;

class ParticipantService
{
    /**
     * Maximum allowed cancellations per participant.
     */
    const MAX_CANCELLATIONS = 3;

    /**
     * Register a new participant.
     *
     * @param array $data
     * @return Participant
     * @throws Exception
     */
    public static function register(array $data): Participant
    {
        // Check cancellation history once before write transaction.
        $cancellationCount = self::getCancellationCount($data['no_telefon']);
        if ($cancellationCount >= self::MAX_CANCELLATIONS) {
            throw new Exception('Registration blocked: Maximum cancellation limit reached.');
        }

        return DB::transaction(function () use ($data): Participant {
            // Row lock prevents overbooking when multiple requests hit the last slot.
            $iftarDay = IftarDay::query()
                ->whereKey($data['iftar_day_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (strtoupper((string) $iftarDay->status) === 'CLOSED') {
                throw new Exception('Registration is closed for this iftar day.');
            }

            $isDuplicate = Participant::query()
                ->where('masjid_id', $iftarDay->masjid_id)
                ->where('iftar_day_id', $data['iftar_day_id'])
                ->where('no_telefon', $data['no_telefon'])
                ->where('is_cancelled', false)
                ->exists();

            if ($isDuplicate) {
                throw new Exception('Duplicate registration is not allowed for this event.');
            }

            $availableSlots = (int) $iftarDay->kapasiti_max - (int) $iftarDay->jumlah_daftar;
            if ($availableSlots < (int) $data['bil_pax']) {
                throw new Exception('Insufficient capacity for this iftar day.');
            }

            $participant = Participant::create([
                'masjid_id' => $iftarDay->masjid_id,
                'iftar_day_id' => $data['iftar_day_id'],
                'nama' => $data['nama'],
                'no_telefon' => $data['no_telefon'],
                'bil_pax' => $data['bil_pax'],
                'checkin_status' => 'PENDING',
                'notes' => $data['notes'] ?? null,
            ]);

            $iftarDay->jumlah_daftar += (int) $data['bil_pax'];

            if ((int) $iftarDay->jumlah_daftar >= (int) $iftarDay->kapasiti_max) {
                $iftarDay->status = 'full';
            }

            $iftarDay->save();

            return $participant;
        });
    }

    /**
     * Cancel a participant's registration.
     *
     * @param int $participantId
     * @return bool
     * @throws Exception
     */
    public static function cancel(int $participantId): bool
    {
        $participant = Participant::findOrFail($participantId);

        if ($participant->is_cancelled) {
            throw new Exception('Registration already cancelled.');
        }

        DB::beginTransaction();
        try {
            // Mark as cancelled
            $participant->update([
                'is_cancelled' => true,
                'cancelled_at' => now(),
                'cancellation_count' => $participant->cancellation_count + 1,
            ]);

            // Decrease iftar day counter
            $iftarDay = IftarDay::findOrFail($participant->iftar_day_id);
            $iftarDay->decrement('jumlah_daftar', $participant->bil_pax);

            // Reopen if was full
            if (strtoupper((string) $iftarDay->status) === 'FULL') {
                $iftarDay->update(['status' => 'open']);
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Check-in a participant.
     *
     * @param int $participantId
     * @return bool
     * @throws Exception
     */
    public static function checkIn(int $participantId): bool
    {
        $participant = Participant::findOrFail($participantId);

        if ($participant->is_cancelled) {
            throw new Exception('Cannot check-in: Registration is cancelled.');
        }

        if ($participant->checkin_status === 'CHECKED_IN') {
            throw new Exception('Already checked in.');
        }

        $participant->update(['checkin_status' => 'CHECKED_IN']);
        
        return true;
    }

    /**
     * Mark participant as no-show.
     *
     * @param int $participantId
     * @return bool
     */
    public static function markNoShow(int $participantId): bool
    {
        $participant = Participant::findOrFail($participantId);
        
        $participant->update(['checkin_status' => 'NO_SHOW']);
        
        return true;
    }

    /**
     * Get total cancellations for a phone number.
     *
     * @param string $phoneNumber
     * @return int
     */
    public static function getCancellationCount(string $phoneNumber): int
    {
        return Participant::where('no_telefon', $phoneNumber)
            ->where('is_cancelled', true)
            ->sum('cancellation_count');
    }

    /**
     * Check if participant can register again.
     *
     * @param string $phoneNumber
     * @return bool
     */
    public static function canRegister(string $phoneNumber): bool
    {
        return self::getCancellationCount($phoneNumber) < self::MAX_CANCELLATIONS;
    }
}