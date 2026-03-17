<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Resources\DashboardResource;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class DashboardController extends BaseController
{
    protected $dashboardService;

    /**
     * DashboardController constructor.
     * @param DashboardService $dashboardService
     */
    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Get user workspaces and portals by user_id.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserWorkspacesAndPortals(Request $request): JsonResponse
    {
        try {
            //$userId = $request->input('user_id');
          
            $responseData = $this->dashboardService->getUserWorkspacesAndPortals($request);

            return $this->successResponse(
                new DashboardResource($responseData),
                trans('dashboard.user_workspaces_portals',)
            );
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }
}
