<?php

namespace App\Http\Controllers\API;

use App\Enums\PermissionEnum;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Requests\Tiles\BulkTilesPositionUpdateRequest;
use App\Http\Requests\Tiles\TilesCreateRequest;
use App\Http\Requests\Tiles\TilesPositionUpdateRequest;
use App\Http\Requests\Tiles\TilesUpdateRequest;
use App\Http\Resources\TilesResource;
use App\Models\Tiles;
use App\Services\TilesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;
use App\Models\Portals;

class TilesController extends BaseController
{
    /**
     * @var TilesService The service for handling section operations.
     */
    protected $TilesService;

    /**
     * @var permissionSlugs The slug for handling section operations.
     */
    protected $permissionSlugs;

    /**
     * TilesController constructor
     *
     * @param TilesService   $TilesService   The service for handling Tiles related operations.
     */
    public function __construct(TilesService $TilesService)
    {
        $this->TilesService    = $TilesService;
        $this->moduleName      = trans("Tiles.module_name");
        $this->permissionSlugs = PermissionEnum::Slugs->getAll();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Mixed
     */
    public function index(Request $request)
    {
        try {
            $TilesData = $this->TilesService->getTilesCollection($request);
            //dd($TilesData);
            $portal = Portals::where('slug', $request->input('slug'))->firstOrFail();
            if ($portal) {
                if ($portal->privacy === "private") {
                    $this->authorize($this->permissionSlugs["portals"]["create"], Portals::class);
                }
                return $this->successResponse(
                    TilesResource::collection($TilesData),
                    trans(
                        'common.fetch_successfully',
                        ['module' => $this->moduleName]
                    )
                );
            }
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(TilesCreateRequest $request): JsonResponse
    {
        try {
            //   $input = $request->all();
            $Tiles = $this->TilesService->createTiles($request);
            return $this->successResponse(
                new TilesResource($Tiles),
                trans(
                    'common.create_successfully',
                    ['module' => $this->moduleName]
                )
            );
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Display the specified Tiles.
     *
     * @param int $TilesId The ID of the Tiles to view.
     *
     * @return Mixed
     */
    public function show(int $Id, Request $request): mixed
    {
        try {
            $include = $request->get('include') ? [$request->get('include')] : [];
            $Tiles   = $this->TilesService->findByTilesId($Id, $include);
            return $this->successResponse(
                new TilesResource($Tiles),
                trans(
                    'common.fetch_successfully',
                    ['module' => $this->moduleName]
                )
            );
        } catch (\Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Update the Tiles in storage.
     *
     * @param int  $id  of the Tiles.
     * @param TilesUpdateRequest $request The request containing the validated data for updating Tiles.
     *
     * @return JsonResponse
     */
    public function updateTiles(int $tilesId, TilesUpdateRequest $TilesRequest): JsonResponse
    {
        try {
            $this->authorize($this->permissionSlugs["portals"]["create"], Tiles::class);
            $data  = $TilesRequest->validated();
            $Tiles = $this->TilesService->updateTiles($tilesId, $TilesRequest);

            return $this->successResponse(
                new TilesResource($Tiles),
                trans(
                    'common.update_successfully',
                    ['module' => $this->moduleName]
                )
            );
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Update the Tiles in storage.
     *
     * @param int  $id  of the Tiles.
     * @param TilesUpdateRequest $request The request containing the validated data for updating Tiles.
     *
     * @return JsonResponse
     */
    public function updatePosition(int $tilesId, TilesPositionUpdateRequest $TilesRequest): JsonResponse
    {
        try {
            $this->authorize($this->permissionSlugs["portals"]["create"], Tiles::class);
            $data  = $TilesRequest->validated();
            $Tiles = $this->TilesService->updatePoisitionTiles($tilesId, $TilesRequest);

            return $this->successResponse(
                new TilesResource($Tiles),
                trans(
                    'common.update_successfully',
                    ['module' => $this->moduleName]
                )
            );
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Remove the specified Tiles from storage.
     *
     * @param Request $request The request object.
     * @param int  $TilesId id of the Tiles.
     *
     * @return Mixed
     */
    public function destroy(Request $request, int $tilesId): Mixed
    {
        try {
            $this->authorize($this->permissionSlugs["portals"]["create"], Tiles::class);
            $Tiles = $this->TilesService->deleteTilesById($tilesId);
            if ($Tiles) {
                return $this->successResponse(
                    [],
                    trans(
                        'common.delete_successfully',
                        ['module' => $this->moduleName]
                    )
                );
            }
            return $this->sendError('Something Wrong.', trans('common.something_wrong'), 401);
        } catch (\Throwable $throwable) {
            report($throwable);
            return $this->sendError('error', $throwable->getMessage(), 500);
            // return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Update the position of the specified Tiles.
     *
     * @param BulkTilesPositionUpdateRequest $request The request object.
     *
     * @return JsonResponse
     */
    public function updateTilePosition(BulkTilesPositionUpdateRequest $request): JsonResponse
    {
        try {
            $tiles = $request->validated()['tiles'];

            // Perform the bulk update
            $updatedTiles = $this->TilesService->bulkUpdateTilePositions($tiles);

            return $this->successResponse(
                TilesResource::collection($updatedTiles),
                trans('common.update_successfully', ['module' => $this->moduleName . ' Position'])
            );
        } catch (Throwable $e) {
            report($e);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
