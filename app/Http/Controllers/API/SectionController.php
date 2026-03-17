<?php

namespace App\Http\Controllers\API;

use App\Enums\PermissionEnum;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Requests\Section\BulkSectionsPositionUpdateRequest;
use App\Http\Requests\Section\SectionCreateRequest;
use App\Http\Requests\Section\SectionsPositionUpdateRequest;
use App\Http\Requests\Section\SectionUpdateRequest;
use App\Http\Resources\BaseCollection;
use App\Http\Resources\SectionResource;
use App\Http\Resources\SectionAssetManagementResource;
use App\Http\Resources\SectionGuestResource;
use Illuminate\Support\Facades\Auth;
use App\Services\SectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;
use App\Models\Sections;


class SectionController extends BaseController
{
    /**
     * @var SectionService The service for handling section operations.
     */
    protected $sectionService;

    /**
     * @var permissionSlugs The slug for handling section operations.
     */
    protected $permissionSlugs;

    /**
     * sectionController constructor
     *
     * @param sectionService   $sectionService   The service for handling section related operations.
     */
    public function __construct(SectionService $sectionService)
    {
        $this->sectionService  = $sectionService;
        $this->moduleName      = trans("section.module_name");
        $this->permissionSlugs = PermissionEnum::Slugs->getAll();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Mixed
     */
    public function index(Request $request): mixed
    {
        $sectionData = $this->sectionService->getSectionCollection($request);
        try {
            return $this->successResponse(
                new BaseCollection($sectionData, SectionResource::class),
                trans(
                    'common.fetch_successfully',
                    ['module' => $this->moduleName]
                )
            );
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Mixed
     */
    public function getAllSection(Request $request): mixed
    {
        $sectionData = $this->sectionService->getSectionCollection($request);

        try {
            return $this->successResponse(
                SectionResource::collection($sectionData),
                trans(
                    'common.fetch_successfully',
                    ['module' => $this->moduleName]
                )
            );
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
    public function store(SectionCreateRequest $request): JsonResponse
    {
        // $this->authorize($this->permissionSlugs["sections"]["create"], Sections::class);

        try {
            $input   = $request->all();
            $section = $this->sectionService->createSection($input);
            return $this->successResponse(
                new SectionResource($section),
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
     * Display the specified Section.
     *
     * @param int $sectionId The ID of the Section to view.
     *
     * @return Mixed
     */
    public function show(int $Id, Request $request): mixed
    {
        $this->authorize($this->permissionSlugs["sections"]["list"], Sections::class);

        try {

            $section = $this->sectionService->findByWorkspaceId($Id, $request);
            return $this->successResponse(
                new SectionResource($section),
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
     * Update the Section in storage.
     *
     * @param int  $id  of the Section.
     * @param SectionUpdateRequest $request The request containing the validated data for updating Section.
     *
     * @return JsonResponse
     */
    public function update(int $sectionId, SectionUpdateRequest $sectionRequest): JsonResponse
    {
        $this->authorize($this->permissionSlugs["sections"]["update"], Sections::class);

        try {
            $data    = $sectionRequest->validated();
            $section = $this->sectionService->updateSection($sectionId, $data);

            return $this->successResponse(
                new SectionResource($section),
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
     * Remove the specified section from storage.
     *
     * @param Request $request The request object.
     * @param int  $section id of the section.
     *
     * @return Mixed
     */
    public function destroy(Request $request, int $sectionId): Mixed
    {
        $this->authorize($this->permissionSlugs["sections"]["delete"], Sections::class);
        try {
            $workspace = $this->sectionService->deleteSectionById($sectionId);
            if ($workspace) {
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
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Update the specified section in storage.
     *
     * @param int  $sectionsId id of the section.
     * @param SectionsPositionUpdateRequest $SectionsRequest The request containing the validated data for updating section.
     *
     * @return JsonResponse
     */
    public function updatePosition(int $sectionsId, SectionsPositionUpdateRequest $SectionsRequest): JsonResponse
    {
        try {
            $data     = $SectionsRequest->validated();
            $Sections = $this->sectionService->updatePoisitionSections($sectionsId, $SectionsRequest);

            return $this->successResponse(
                new SectionResource($Sections),
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
     * Update the position of multiple Sections in one go.
     *
     * @param BulkSectionsPositionUpdateRequest $request The request containing the validated data for updating the Sections.
     *
     * @return JsonResponse
     */
    public function updateSectionsPosition(BulkSectionsPositionUpdateRequest $request): JsonResponse
    {
        try {
            $sections = $request->validated()['sections'];

            // Perform the bulk update
            $updatedSections = $this->sectionService->bulkUpdateSectionsPosition($sections);

            return $this->successResponse(
                SectionResource::collection($updatedSections),
                trans('common.update_successfully', ['module' => $this->moduleName . ' Position'])
            );
        } catch (Throwable $e) {
            report($e);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Mixed
     */
    public function getAllAssets(Request $request): mixed
    {
        $sectionData = $this->sectionService->getSectionAssets($request);
        try {
            $user = Auth::user();
            $hasWorkspace = true; // user_workspaces check removed
            if ($user->hasRole('Super Admin') || $hasWorkspace) {
                return $this->successResponse(
                    SectionAssetManagementResource::collection($sectionData),
                    trans(
                        'common.fetch_successfully',
                        ['module' => $this->moduleName]
                    )
                );
            }

            return $this->sendError('Unauthorised', trans('common.unauthorised'), 401);
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Fetch all assets of a given section id
     * 
     * @param int $sectionId The id of the section
     * @param Request $request The request object
     * 
     * @return mixed
     */
    public function getAllAssetsId(int $sectionId, Request $request): mixed
    {
        $sectionData = $this->sectionService->getSectionAssetsId($sectionId, $request);
        try {
            $user = Auth::user();
            $hasWorkspace = true; // user_workspaces check removed
            if ($user->hasRole('Super Admin') || $hasWorkspace) {
                return $this->successResponse(
                    SectionAssetManagementResource::collection($sectionData),
                    trans(
                        'common.fetch_successfully',
                        ['module' => $this->moduleName]
                    )
                );
            }

            return $this->sendError('Unauthorised', trans('common.unauthorised'), 401);
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    public function suggestions(Request $request)
    {
         try {
            $suggestions = $this->sectionService->getTheSuggestion($request);

            return $this->successResponse($suggestions,
                trans('common.fetch_successfully', ['module' => $this->moduleName])
            );
        } catch (Throwable $e) {
            report($e);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
