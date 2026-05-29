<?php

namespace App\Http\Controllers\OrdiioApiController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Ordiio\SourceAudioApiService;

class OrdiioFilterController extends Controller
{
protected $trackService;
public function __construct(SourceAudioApiService $trackService)
    {
        $this->trackService = $trackService;
    }
public function listTracksFilter(Request $request)
    {
        try {
            $filters = $request->all();
            $limit   = $request->query('limit', 50);
            $offset  = $request->query('offset', 0);
            $tracks = $this->trackService->searchTracks($filters, $limit, $offset);
            return response()->json([
                'count' => $tracks->count(),
                'tracks' => $tracks->values(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error'   => 'Failed to fetch tracks',
                'message' => $e->getMessage()
            ], 500);
        }
    }
public function availableFilters(Request $request)
    {
        try {
            $filters = $this->trackService->availableFilters($request);

            return response()->json([
                'success' => true,
                'filters' => $filters
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Server error',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
