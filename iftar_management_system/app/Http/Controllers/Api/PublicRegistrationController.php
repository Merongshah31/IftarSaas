<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Participant\RegisterParticipantRequest;
use App\Http\Resources\IftarDayResource;
use App\Http\Resources\ParticipantResource;
use App\Models\IftarDay;
use App\Models\Masjid;
use App\Services\ParticipantService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicRegistrationController extends Controller
{
    /**
     * Public list of open iftar days for a specific masjid.
     */
    public function listOpenIftarDays(Masjid $masjid, Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 100);

        $iftarDays = IftarDay::withoutGlobalScopes()
            ->where('masjid_id', $masjid->id)
            ->where('status', 'open')
            ->orderBy('tarikh')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => IftarDayResource::collection($iftarDays),
            'meta' => [
                'current_page' => $iftarDays->currentPage(),
                'total' => $iftarDays->total(),
                'per_page' => $iftarDays->perPage(),
                'last_page' => $iftarDays->lastPage(),
            ],
        ]);
    }

    /**
     * Public participant registration for a specific masjid.
     */
    public function registerParticipant(Masjid $masjid, RegisterParticipantRequest $request): JsonResponse
    {
        TenantContext::setTenant($masjid->id);

        try {
            $validated = $request->validated();

            $iftarDay = IftarDay::withoutGlobalScopes()
                ->where('id', $validated['iftar_day_id'])
                ->where('masjid_id', $masjid->id)
                ->first();

            if (!$iftarDay) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hari iftar tidak sah untuk masjid dipilih',
                ], 422);
            }

            $participant = ParticipantService::register($validated);

            return response()->json([
                'success' => true,
                'message' => 'Pendaftaran berjaya! Sila hadir pada waktu yang ditetapkan.',
                'data' => new ParticipantResource($participant->load('iftarDay')),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pendaftaran gagal',
                'error' => $e->getMessage(),
            ], 400);
        } finally {
            TenantContext::clearTenant();
        }
    }
}
