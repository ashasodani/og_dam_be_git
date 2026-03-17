<?php
namespace App\Http\Controllers\API;

use App\Enums\PermissionEnum;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Requests\Workspace\CheckUniqueRequest;
use App\Http\Requests\Workspace\WorkspaceCreateRequest;
use App\Http\Requests\Workspace\WorkspaceUpdateRequest;
use App\Http\Resources\WorkspaceResource;
use App\Models\Workspaces;
use App\Services\WorkspaceService;
use Illuminate\Http\JsonResponse;use Illuminate\Http\Request;use Throwable;

class WorkspaceController extends BaseController
{
    /**
     * @var WorkspaceService The service for handling section operations.
     */
    protected $workspaceService;

    /**
     * @var permissionSlugs The slug for handling section operations.
     */
    protected $permissionSlugs;

    /**
     * WorkspaceController constructor
     *
     * @param WorkspaceService   $workspaceService   The service for handling Workspace related operations.
     */
    public function __construct(WorkspaceService $workspaceService)
    {
        $this->workspaceService = $workspaceService;
        $this->moduleName       = trans("workspace.module_name");
        $this->permissionSlugs  = PermissionEnum::Slugs->getAll();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Mixed
     */
    public function index(Request $request): mixed
    {
        $this->authorize($this->permissionSlugs["workspaces"]["list"], Workspaces::class);
        $workspaceData = $this->workspaceService->getWorkspaceCollection($request);

        try {
            return $this->successResponse(
                WorkspaceResource::collection($workspaceData),
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
    public function store(WorkspaceCreateRequest $request, Workspaces $model): JsonResponse
    {

        //$this->authorize($this->permissionSlugs["workspaces"]["create"], Workspaces::class);

        try {
            $workspace = $this->workspaceService->createWorkspace($request);
            return $this->successResponse(
                new WorkspaceResource($workspace),
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
     * Display the specified Workspace.
     *
     * @param int $workspaceId The ID of the Workspace to view.
     *
     * @return Mixed
     */
    public function show(int $Id, Request $request): mixed
    {
        $this->authorize($this->permissionSlugs["workspaces"]["list"], Workspaces::class);

        try {
            $include   = $request->get('include') ? [$request->get('include')] : [];
            $workspace = $this->workspaceService->findByWorkspaceId($Id, $include);
            return $this->successResponse(
                new WorkspaceResource($workspace),
                trans(
                    'common.fetch_successfully',
                    ['module' => $this->moduleName]
                ));
        } catch (\Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Update the Workspace in storage.
     *
     * @param int  $id Uuid of the workspace.
     * @param WorkspaceUpdateRequest $request The request containing the validated data for updating workspace.
     *
     * @return JsonResponse
     */
    public function update(int $workspaceId, WorkspaceUpdateRequest $workspaceRequest): JsonResponse
    {
        $this->authorize($this->permissionSlugs["workspaces"]["update"], Workspaces::class);

        try {
            $data      = $workspaceRequest->validated();
            $workspace = $this->workspaceService->updateWorkspace($workspaceId, $data);

            return $this->successResponse(
                new WorkspaceResource($workspace),
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
     * Update the Workspace in storage.
     *
     * @param int  $workspaceId Uuid of the workspace.
     * @param WorkspaceUpdateRequest $workspaceRequest The request containing the validated data for updating workspace.
     *
     * @return JsonResponse
     */
    public function updateWorkspace(int $workspaceId, WorkspaceUpdateRequest $workspaceRequest): JsonResponse
    {
        $this->authorize($this->permissionSlugs["workspaces"]["update"], Workspaces::class);

        try {
            $workspace = $this->workspaceService->updateWorkspace($workspaceId, $workspaceRequest);

            return $this->successResponse(
                new WorkspaceResource($workspace),
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
     * Remove the specified Workspace from storage.
     *
     * @param Request $request The request object.
     * @param int  $workspaceid id of the workspace.
     *
     * @return Mixed
     */
    public function destroy(int $workspaceId): Mixed
    {
        // $this->authorize($this->permissionSlugs["workspaces"]["delete"], Workspaces::class);

        try {
            $result = $this->workspaceService->deleteWorkspaceById($workspaceId);

            if (! $result['success']) {
                switch ($result['reason']) {
                    case 'workspace_only_one_left':
                        return $this->sendError(
                            trans('common.workspace_only_one_left'),
                            ['reason' => 'Only 1 Workspace left.'],
                            420
                        );                   
                    case 'workspace_has_assets':
                        return $this->sendError(
                            trans('common.workspace_has_assets'),
                            ['reason' => 'This workspace has assigned assets.'],
                            420
                        );
                    case 'workspace_not_found':
                        return $this->sendError(
                            trans('common.not_found', ['module' => $this->moduleName]),
                            ['reason' => trans('common.not_found', ['module' => $this->moduleName])],
                            404
                        );

                    default:
                        return $this->sendError(
                            trans('common.not_found', ['module' => $this->moduleName]),
                            ['reason' => 'No Workspace Found.'],
                            404
                        );
                }
            }

            return $this->successResponse(
                $workspaceId,
                trans('common.delete_successfully', ['module' => $this->moduleName])
            );
        } catch (\Throwable $throwable) {
            report($throwable);
            return $this->sendError('error', $throwable->getMessage(), 500);
        }
    }

    /**
     * Check if a workspace attribute is unique.
     *
     * @param CheckUniqueRequest $request The request containing the attribute to check for uniqueness.
     * @return JsonResponse A JSON response indicating whether the attribute is unique or not.
     */

    public function checkUnique(CheckUniqueRequest $request)
    {
        try {
            $data = $request->validated();

            if ($data) {
                return $this->successResponse($data,
                    trans('common.found_unique', ['module' => ucfirst($data['type'])])
                );
            }
            return $this->sendValidation($data->errors(), trans('validation.failed'), 420);

        } catch (Throwable $throwable) {
            report($throwable);
            return $this->sendError('error', $throwable->getMessage(), 500);
        }
    }

     /**
     * Display the specified Workspace.
     *
     * @param int $workspaceId The ID of the Workspace to view.
     *
     * @return Mixed
     */
    public function getSlugWorkspace(string $slug, Request $request): mixed
    {
        try {
            $workspace = $this->workspaceService->findByWorkspaceSlug($slug);
             if($workspace) {
                return $this->successResponse(
                new WorkspaceResource($workspace),
                trans(
                    'common.fetch_successfully',
                    ['module' => $this->moduleName]
                ));
            } else {
                return $this->sendError('Not exist', trans('common.something_wrong'), 404);
            }
           
        } catch (\Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

}
