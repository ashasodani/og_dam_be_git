<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Http\JsonResponse;
use App\Services\PortalService;
use App\Models\Portals;
use App\Http\Resources\PortalResource;
use App\Http\Requests\Portal\{
    PortalCreateRequest,
    PortalUpdateRequest,
    RelatedPortalCreateRequest
};
use Throwable;
use App\Enums\PermissionEnum;
use Illuminate\Support\Facades\Auth;


class PortalController extends BaseController
{
    /**
     * @var PortalService The service for handling section operations.
     */
    protected $portalService;

     /**
     * @var permissionSlugs The slug for handling section operations.
     */
    protected $permissionSlugs;


   
    /**
     * PortalController constructor
     *
     * @param PortalService   $portalService   The service for handling Portal related operations.
     */
    public function __construct(PortalService $portalService)
    {
        $this->portalService = $portalService;
        $this->moduleName = trans("portal.module_name");
        $this->permissionSlugs = PermissionEnum::Slugs->getAll();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Mixed
     */
    public function index(Request $request): mixed
    {
        $this->authorize($this->permissionSlugs["portals"]["list"], Portals::class);
        $include = $request->has('include') ? array($request->get('include')) : [];
        $PortalData = $this->portalService->getPortalCollection($include);
        try {
            return $this->successResponse(
                PortalResource::collection($PortalData),
                trans(
                    'common.fetch_successfully',
                    ['module' =>  $this->moduleName]
                ));
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
    public function store(PortalCreateRequest $request): JsonResponse
    {
        try {
            $this->authorize($this->permissionSlugs["portals"]["create"], Portals::class);
            $Portal = $this->portalService->createPortal($request);
            return $this->successResponse(
                new PortalResource($Portal),
                trans(
                    'common.create_successfully',
                    ['module' =>  $this->moduleName]
                ));
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Display the specified Portal.
     *
     * @param int $portalId The ID of the Portal to view.
     *
     * @return Mixed
     */
    public function show(int $Id, Request $request): mixed
    {
        try {
            $this->authorize($this->permissionSlugs["portals"]["list"], Portals::class);
            $include = $request->get('include') ? array($request->get('include')) : [];
            $Portal = $this->portalService->findByPortalId($Id, $include);
            return $this->successResponse(
                new PortalResource($Portal),
                trans(
                    'common.fetch_successfully',
                    ['module' =>  $this->moduleName]
                ));
        } catch (\Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Update the Portal in storage.
     *
     * @param int  $id  of the Portal.
     * @param PortalUpdateRequest $request The request containing the validated data for updating Portal.
     *
     * @return JsonResponse
     */
    // public function update(int $portalId, PortalUpdateRequest $portalRequest): JsonResponse
    // {
    //     try {
    //         // $this->authorize($this->permissionSlugs["portals"]["update"], Portals::class);
    //         $data = $portalRequest->validated();
    //         $Portal = $this->portalService->updatePortal($portalId, $portalRequest);

    //         return $this->successResponse(
    //             new PortalResource($Portal),
    //             trans(
    //                 'common.update_successfully',
    //                 ['module' =>  $this->moduleName]
    //             ));
    //     } catch (Throwable $throwable) {
    //         report($throwable);
    //         return response()->json(['error' => $throwable->getMessage()], 500);
    //     }
    // }

     /**
     * Update the Portal in storage.
     *
     * @param int  $id  of the Portal.
     * @param PortalUpdateRequest $request The request containing the validated data for updating Portal.
     *
     * @return JsonResponse
     */
    public function updatePortal(int $portalId, PortalUpdateRequest $portalRequest): JsonResponse
    {
        try {
            $this->authorize($this->permissionSlugs["portals"]["update"], Portals::class);
            $data = $portalRequest->validated();
            $Portal = $this->portalService->updatePortal($portalId, $portalRequest);

            return $this->successResponse(
                new PortalResource($Portal),
                trans(
                    'common.update_successfully',
                    ['module' =>  $this->moduleName]
                ));
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Remove the specified Portal from storage.
     *
     * @param Request $request The request object.
     * @param int  $portalId id of the Portal.
     *
     * @return Mixed
     */
    public function destroy(Request $request, int $portalId): Mixed
    {
            try {
                $this->authorize($this->permissionSlugs["portals"]["delete"], Portals::class);
                $Portal = $this->portalService->deletePortalById($portalId);
                if($Portal) {
                    return $this->successResponse([],
                        trans(
                            'common.delete_successfully',
                            ['module' =>  $this->moduleName]
                        ));
                }
                return $this->sendError('Something Wrong.', trans('common.something_wrong'), 401);
            } catch (\Throwable $throwable) {
                report($throwable);
                return $this->sendError('error', $throwable->getMessage(), 500);
               // return response()->json(['error' => $throwable->getMessage()], 500);
            }
    }

     /**
     * Store a newly created related portal in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createRelatedPortal(RelatedPortalCreateRequest $request): JsonResponse
    {
        try {
            $portals = $this->portalService->createRelatedPortal($request);
            return $this->successResponse(
                new PortalResource($portals),
                trans(
                    'common.create_successfully',
                    ['module' =>  $this->moduleName]
                ));
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

     /**
     * get a newly created related portal in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRelatedPortal(Request $request): JsonResponse
    {
        $PortalData = $this->portalService->getRelatedPortalCollection($request);
        try {
            return $this->successResponse(
                PortalResource::collection($PortalData),
                trans(
                    'common.fetch_successfully',
                    ['module' =>  $this->moduleName]
                ));
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

      /**
     * Display the specified Workspace.
     *
     * @param int $workspaceId The ID of the Workspace to view.
     *
     * @return Mixed
     */
    public function getSlugPortals(string $slug, Request $request): mixed
    {
        try {
            $Portal = $this->portalService->findByPortalSlug($slug);
            if($Portal){
                if($Portal->privacy === "public") {
                return $this->successResponse(
                    new PortalResource($Portal),
                    trans(
                        'common.fetch_successfully',
                        ['module' =>  $this->moduleName]
                    ));
                } else {
                    // if (!Auth::check()) {
                    //         return response()->json(['error' => 'Authentication required.'], 405);
                    // }
                    $this->authorize($this->permissionSlugs["portals"]["create"], Portals::class);
                       
                    return $this->successResponse(
                    new PortalResource($Portal),
                    trans(
                        'common.fetch_successfully',
                        ['module' =>  $this->moduleName]
                    ));
                }
            }else{
                return $this->sendError('Something Wrong.', trans('common.something_wrong'), 401);
            }
           
           
        } catch (\Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }
}
