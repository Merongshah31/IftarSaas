<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Participant\RegisterParticipantRequest;
use App\Http\Resources\ParticipantResource;
use App\Models\Participant;
use App\Services\ParticipantService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ParticipantController extends Controller
{
    /**
     * Display a listing of participants.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Participant::query();

        // Filter by iftar day
        if ($request->has('iftar_day_id')) {
            $query->where('iftar_day_id', $request->iftar_day_id);
        }

        // Filter by checkin status
        if ($request->has('checkin_status')) {
            $query->where('checkin_status', $request->checkin_status);
        }

        // Filter by cancelled status
        if ($request->has('is_cancelled')) {
            $query->where('is_cancelled', $request->boolean('is_cancelled'));
        }

        // Search by name or phone
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('no_telefon', 'like', "%{$search}%");
            });
        }

        // Include relationships
        if ($request->has('include')) {
            $includes = explode(',', $request->include);
            $query->with($includes);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $participants = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => ParticipantResource::collection($participants),
            'meta' => [
                'current_page' => $participants->currentPage(),
                'total' => $participants->total(),
                'per_page' => $participants->perPage(),
                'last_page' => $participants->lastPage(),
            ],
        ]);
    }

    /**
     * Register a new participant.
     */
    public function store(RegisterParticipantRequest $request): JsonResponse
    {
        try {
            $participant = ParticipantService::register($request->validated());

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
        }
    }

    /**
     * Display the specified participant.
     */
    public function show(Participant $participant): JsonResponse
    {
        // Load relationships if requested
        $includes = request()->get('include');
        if ($includes) {
            $participant->load(explode(',', $includes));
        }

        return response()->json([
            'success' => true,
            'data' => new ParticipantResource($participant),
        ]);
    }

    /**
     * Cancel participant registration.
     */
    public function cancel(Participant $participant): JsonResponse
    {
        try {
            ParticipantService::cancel($participant->id);

            return response()->json([
                'success' => true,
                'message' => 'Pendaftaran telah dibatalkan',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pembatalan gagal',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Check-in a participant.
     */
    public function checkIn(Participant $participant): JsonResponse
    {
        try {
            ParticipantService::checkIn($participant->id);

            return response()->json([
                'success' => true,
                'message' => 'Check-in berjaya',
                'data' => new ParticipantResource($participant->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Check-in gagal',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Mark participant as no-show.
     */
    public function noShow(Participant $participant): JsonResponse
    {
        try {
            ParticipantService::markNoShow($participant->id);

            return response()->json([
                'success' => true,
                'message' => 'Ditandakan sebagai tidak hadir',
                'data' => new ParticipantResource($participant->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menandakan status',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Check if phone number can register.
     */
    public function checkEligibility(Request $request): JsonResponse
    {
        $request->validate([
            'no_telefon' => ['required', 'string'],
        ]);

        $canRegister = ParticipantService::canRegister($request->no_telefon);
        $cancellationCount = ParticipantService::getCancellationCount($request->no_telefon);

        return response()->json([
            'success' => true,
            'data' => [
                'can_register' => $canRegister,
                'cancellation_count' => $cancellationCount,
                'max_cancellations' => ParticipantService::MAX_CANCELLATIONS,
                'remaining_chances' => max(0, ParticipantService::MAX_CANCELLATIONS - $cancellationCount),
            ],
        ]);
    }
}
