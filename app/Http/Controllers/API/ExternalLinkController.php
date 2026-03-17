<?php

namespace App\Http\Controllers\API;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\ExternalLinkService;
use App\Models\Portals;
use App\Http\Resources\ExternalLinkResource;
use App\Http\Requests\ExternalLink\LinkUpdateRequest;
use App\Http\Requests\Tiles\BulkTilesPositionUpdateRequest;
use App\Http\Requests\ExternalLink\ExternalLinkCreateRequest;
use App\Http\Requests\ExternalLink\ExternalLinkUpdateRequest;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Requests\ExternalLink\BulkAdditionalLinkPositionUpdateRequest;
use App\Enums\PermissionEnum;

class ExternalLinkController extends BaseController
{
       /**
     * @var permissionSlugs The slug for handling section operations.
     */
    protected $permissionSlugs;
    /**
     * @var ExternalLinkService The service for handling section operations.
     */
    protected $externalLinkService;

    /**
     * externalLinksController constructor
     *
     * @param ExternalLinkService   $externalLinksService   The service for handling externalLinks related operations.
     */
    public function __construct(ExternalLinkService $externalLinkService)
    {
        $this->externalLinkService = $externalLinkService;
        $this->moduleName          = trans("externallink.module_name");
        $this->permissionSlugs = PermissionEnum::Slugs->getAll();
        //$this->authorizeResource(externalLinkss::class, 'externalLinks');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Mixed
     */
    public function index(Request $request)
    {
       
        try {
             $portal = Portals::where('slug', $request->input('slug'))->firstOrFail();
                if($portal){
                        if($portal->privacy === "private") {
                            $this->authorize($this->permissionSlugs["portals"]["create"], Portals::class);
                        } 
                        $externalLinksData = $this->externalLinkService->getExternalLinkCollection($request);
                        return $this->successResponse(
                            ExternalLinkResource::collection($externalLinksData),
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
    public function store(ExternalLinkCreateRequest $request): JsonResponse
    {
        try {
            $externalLinks = $this->externalLinkService->createExternalLink($request);
            return $this->successResponse(
                new ExternalLinkResource($externalLinks),
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
     * Display the specified externalLinks.
     *
     * @param int $externalLinksId The ID of the externalLinks to view.
     *
     * @return Mixed
     */
    public function show(int $Id, Request $request): mixed
    {
        try {
            $include       = $request->get('include') ? [$request->get('include')] : [];
            $externalLinks = $this->externalLinkService->findByExternalLinkId($Id, $include);
            return $this->successResponse(
                new ExternalLinkResource($externalLinks),
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
     * Update the externalLinks in storage.
     *
     * @param int  $id  of the externalLinks.
     * @param ExternalLinksUpdateRequest $request The request containing the validated data for updating externalLinks.
     *
     * @return JsonResponse
     */
    public function updateLinks(int $externalLinksId, ExternalLinkUpdateRequest $externalLinksRequest): JsonResponse
    {
        try {
            $data          = $externalLinksRequest->validated();
            $externalLinks = $this->externalLinkService->updateExternalLinks($externalLinksId, $externalLinksRequest);

            return $this->successResponse(
                new ExternalLinkResource($externalLinks),
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
     * Remove the specified externalLinks from storage.
     *
     * @param Request $request The request object.
     * @param int  $externalLinksId id of the externalLinks.
     *
     * @return Mixed
     */
    public function destroy(Request $request, int $externalLinksId): Mixed
    {
        try {
            $externalLinks = $this->externalLinkService->deleteExternalLinkById($externalLinksId);
            if ($externalLinks) {
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
     * Update the Tiles in storage.
     *
     * @param int  $id  of the Tiles.
     * @param TilesUpdateRequest $request The request containing the validated data for updating Tiles.
     *
     * @return JsonResponse
     */
    public function updatePosition(int $linkId, LinkUpdateRequest $linkRequest): JsonResponse
    {
        try {

            $data  = $linkRequest->validated();
            $Tiles = $this->externalLinkService->updateExternalLinksPosition($linkId, $linkRequest);

            return $this->successResponse(
                new ExternalLinkResource($Tiles),
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

    public function updateAdditionalLinkPosition(BulkAdditionalLinkPositionUpdateRequest $request): JsonResponse
    {
        try {
            $tiles = $request->validated()['additional_links'];

            // Perform the bulk update
            $updatedLinks = $this->externalLinkService->bulkUpdateAdditionalLinkPositions($tiles);

            return $this->successResponse(
                ExternalLinkResource::collection($updatedLinks),
                trans('common.update_successfully', ['module' => $this->moduleName . ' Position'])
            );
        } catch (Throwable $e) {
            report($e);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
