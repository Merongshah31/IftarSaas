<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IftarDay\StoreIftarDayRequest;
use App\Http\Requests\IftarDay\UpdateIftarDayRequest;
use App\Http\Resources\IftarDayResource;
use App\Models\IftarDay;
use App\Services\IftarDayService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class IftarDayController extends Controller
{
    /**
     * Display a listing of iftar days.
     */
    public function index(Request $request): JsonResponse
    {
        $query = IftarDay::query();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('tarikh', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('tarikh', '<=', $request->to_date);
        }

        // Include relationships
        if ($request->has('include')) {
            $includes = explode(',', $request->include);
            $query->with($includes);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $iftarDays = $query->orderBy('tarikh', 'asc')->paginate($perPage);

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
     * Store a newly created iftar day.
     */
    public function store(StoreIftarDayRequest $request): JsonResponse
    {
        try {
            $iftarDay = IftarDayService::create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Iftar day created successfully',
                'data' => new IftarDayResource($iftarDay),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create iftar day',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified iftar day.
     */
    public function show(IftarDay $iftarDay): JsonResponse
    {
        // Load relationships if requested
        $includes = request()->get('include');
        if ($includes) {
            $iftarDay->load(explode(',', $includes));
        }

        return response()->json([
            'success' => true,
            'data' => new IftarDayResource($iftarDay),
        ]);
    }

    /**
     * Update the specified iftar day.
     */
    public function update(UpdateIftarDayRequest $request, IftarDay $iftarDay): JsonResponse
    {
        try {
            $updated = IftarDayService::update($iftarDay->id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Iftar day updated successfully',
                'data' => new IftarDayResource($updated),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update iftar day',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified iftar day.
     */
    public function destroy(IftarDay $iftarDay): JsonResponse
    {
        try {
            IftarDayService::delete($iftarDay->id);

            return response()->json([
                'success' => true,
                'message' => 'Iftar day deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete iftar day',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get statistics for an iftar day.
     */
    public function statistics(IftarDay $iftarDay): JsonResponse
    {
        $stats = IftarDayService::getStatistics($iftarDay->id);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Close iftar day for registration.
     */
    public function close(IftarDay $iftarDay): JsonResponse
    {
        try {
            IftarDayService::close($iftarDay->id);

            return response()->json([
                'success' => true,
                'message' => 'Iftar day closed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reopen iftar day for registration.
     */
    public function reopen(IftarDay $iftarDay): JsonResponse
    {
        try {
            IftarDayService::reopen($iftarDay->id);

            return response()->json([
                'success' => true,
                'message' => 'Iftar day reopened successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
