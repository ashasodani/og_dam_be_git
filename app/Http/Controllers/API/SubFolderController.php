<?php
namespace App\Http\Controllers\API;

use App\Enums\PermissionEnum;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Requests\SubFolder\SubFolderCreateRequest;
use App\Http\Requests\SubFolder\SubFolderUpdateRequest;
use App\Http\Resources\SubFolderResource;
use App\Models\SubFolders;
use App\Services\SubFolderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;use Throwable;
use Illuminate\Support\Facades\Auth;

class SubFolderController extends BaseController
{
    /**
     * @var SubFolderService The service for handling section operations.
     */
    protected $subFolderService;

    /**
     * @var permissionSlugs The slug for handling section operations.
     */
    protected $permissionSlugs;

    /**
     * SubFolderController constructor
     *
     * @param SubFolderService   $subFolderService   The service for handling SubFolder related operations.
     */
    public function __construct(SubFolderService $subFolderService)
    {
        $this->subFolderService = $subFolderService;
        $this->moduleName       = trans("subfolder.module_name");
        $this->permissionSlugs  = PermissionEnum::Slugs->getAll();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Mixed
     */
    public function index(Request $request): mixed
    {
        $subFolderData = $this->subFolderService->getSubFolderCollection($request);

        try {
            return $this->successResponse(
                SubFolderResource::collection($subFolderData),
                trans(
                    'common.fetch_successfully',
                    ['module' => $this->moduleName]
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
    public function store(SubFolderCreateRequest $request, SubFolders $model): JsonResponse
    {
        try {
            $input     = $request->all();
            $subFolder = $this->subFolderService->createSubFolder($input);
            return $this->successResponse(
                new SubFolderResource($subFolder),
                trans(
                    'common.create_successfully',
                    ['module' => $this->moduleName]
                ));
        } catch (\Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Display the specified SubFolder.
     *
     * @param int $subFolderId The ID of the SubFolder to view.
     *
     * @return Mixed
     */
    public function show(int $Id, Request $request): mixed
    {
        try {
            $user = null;
            if (auth('sanctum')->check()) {
                $user = Auth::user();
            }
            $include   = $request->get('include') ? [$request->get('include')] : [];
            $subFolder = $this->subFolderService->findBySubFolderId($Id, $request);
            if(empty($subFolder)) {
                $subFolder = SubFolders::findOrFail($Id);
                return $this->successResponse(
                    new SubFolderResource($subFolder),
                    trans(
                        'common.fetch_successfully',
                        ['module' => $this->moduleName]
                    ));
            } else{
                return $this->successResponse(
                new SubFolderResource($subFolder),
                trans(
                    'common.fetch_successfully',
                    ['module' => $this->moduleName]
                ));
            }
            
        
        } catch (\Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Update the SubFolder in storage.
     *
     * @param int  $id Uuid of the subFolder.
     * @param SubFolderUpdateRequest $request The request containing the validated data for updating subFolder.
     *
     * @return JsonResponse
     */
    public function update(int $subFolderId, SubFolderUpdateRequest $subFolderRequest): JsonResponse
    {
        try {
            $data      = $subFolderRequest->validated();
            $subFolder = $this->subFolderService->updateSubFolder($subFolderId, $data);

            return $this->successResponse(
                new SubFolderResource($subFolder),
                trans(
                    'common.update_successfully',
                    ['module' => $this->moduleName]
                ));
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Remove the specified SubFolder from storage.
     *
     * @param Request $request The request object.
     * @param int  $subFolderid id of the subFolder.
     *
     * @return Mixed
     */
    public function destroy(Request $request, int $subFolderId): Mixed
    {
        try {
            $subFolder = $this->subFolderService->deleteSubFolderById($subFolderId);
            if ($subFolder) {
                return $this->successResponse([],
                    trans(
                        'common.delete_successfully',
                        ['module' => $this->moduleName]
                    ));
            }
            return $this->sendError('Something Wrong.', trans('common.something_wrong'), 401);
        } catch (\Throwable $throwable) {
            report($throwable);
            return $this->sendError('error', $throwable->getMessage(), 500);
        }
    }
}
