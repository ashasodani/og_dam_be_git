<?php
namespace App\Http\Controllers\API;

use App\Enums\PermissionEnum;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Requests\Role\RoleCreateRequest;
use App\Http\Requests\Role\RoleUpdateRequest;
use App\Http\Resources\PermissionGroupResource;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Services\PermissionService;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;use Throwable;

class RoleController extends BaseController
{
    /**
     * @var RoleService The service for handling admin role operations.
     */
    protected $roleService;

    /**
     * @var PermissionService The service for handling permission operations.
     */
    protected $permissionService;

    /**
     * @var permissionSlugs The slug for handling section operations.
     */
    protected $permissionSlugs;

    /**
     * RoleController constructor.
     *
     * @param RoleService       $roleService         The service for handling admin role operations.
     * @param PermissionService $permissionService   The service for handling permission operations.
     */
    public function __construct(RoleService $roleService, PermissionService $permissionService)
    {
        $this->roleService       = $roleService;
        $this->moduleName        = trans('role.module_name');
        $this->permissionService = $permissionService;
        $this->permissionSlugs   = PermissionEnum::Slugs->getAll();
    }

    /**
     * Display a listing of admin roles.
     *
     * @return View|RedirectResponse
     */
    public function index(Request $request): mixed
    {
        $this->authorize($this->permissionSlugs["roles"]["list"], Role::class);

        $role     = $this->roleService->getRoleCollection($request);
       // $roleData = $role->where('name', '!=', 'Super Admin')->get();
        try {
            return $this->successResponse(
                RoleResource::collection($role),
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
     * Display a listing of  roles respective to permission
     *
     * @return View|RedirectResponse
     */
    public function getPermissionGroup(): mixed
    {
        $this->authorize($this->permissionSlugs["roles"]["create"], Role::class);

        $allPermissions = $this->permissionService->getAllPermissions()->toArray();
        $collection     = collect($allPermissions);
        $permissions    = $collection->groupBy('module_name');
        $formatted      = $permissions->map(function ($perms, $module) {
            return [
                'module'      => $module,
                'permissions' => PermissionGroupResource::collection($perms),
            ];
        })->values();
        try {
            return $this->successResponse($formatted,
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
     * Display a listing of  roles respective to permission
     *
     * @return View|RedirectResponse
     */
    public function getRolePermission(int $roleId): mixed
    {

        $role           = $this->roleService->findByRoleId($roleId)->toArray();
        $allPermissions = $this->permissionService->getAllPermissions()->toArray();
        $collection     = collect($allPermissions);
        $permissions    = $collection->groupBy('module_name');
        $formatted      = $permissions->map(function ($perms, $module) {
            return [
                'module'      => $module,
                'permissions' => PermissionGroupResource::collection($perms),
            ];
        })->values();
        try {
            return $this->successResponse($role,
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
     * Store a newly created  role in storage.
     *
     * @param RoleCreateRequest $request The request containing the validated data for creating a new admin role.
     *
     * @return JsonResponse
     */
    public function store(RoleCreateRequest $request): JsonResponse
    {
        $this->authorize($this->permissionSlugs["roles"]["create"], Role::class);

        try {
            $permission  = $request->only('permissions');
            $requestData = $request->only('name');
            $role        = $this->roleService->createRoleWithPermissions($requestData, $permission['permissions']);
            return $this->successResponse(
                new RoleResource($role),
                trans(
                    'common.create_successfully',
                    ['module' => $this->moduleName]
                ));
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Update the specified admin role in storage.
     *
     * @param string            $roleUuid The UUID of the admin role to update.
     * @param RoleUpdateRequest $request  The request containing the validated data for updating the admin role.
     *
     * @return JsonResponse
     */
    public function update(int $roleId, RoleUpdateRequest $request): JsonResponse
    {
        $this->authorize($this->permissionSlugs["roles"]["update"], Role::class);

        try {
            $requestData = $request->only('name');
            $permission  = $request->only('permissions');
            $role        = $this->roleService->updateRoleWithPermissions($roleId, $requestData, $permission['permissions']);
            return $this->successResponse(
                new RoleResource($role),
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
     * Remove the specified admin role from storage.
     *
     * @param Request $request
     * @param int  $roleId The UUID of the admin role to delete.
     *
     * @return JsonResponse
     */
    public function destroy(Request $request, int $roleId): JsonResponse
    {
        $this->authorize($this->permissionSlugs["roles"]["delete"], Role::class);

        try {
            $deleted = $this->roleService->deleteRole($roleId);

            if ($deleted) {
                return $this->successResponse(
                    new RoleResource([]),
                    trans('common.delete_successfully', ['module' => $this->moduleName])
                );
            }
            return $this->sendError('Role has users', 'This role is assigned to one or more users and cannot be deleted', 420);
        } catch (\Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Display the specified admin user.
     *
     * @param string $roleUuid The UUID of the role to view.
     *
     * @return View|RedirectResponse
     */
    public function view(string $roleUuid)
    {

    }

    /**
     * @purpose assign role to  user
     * @param Request $request
     * @return string \Illuminate\Http\Response
     */
    public function assignRole(Request $request)
    {
        //$this->authorize($this->permissionSlugs["roles"]["create"], Role::class);

        try {
            $role = $this->roleService->syncRole($request->role_id, $request->user_id);
            return $this->successResponse([$role->name], trans(
                'common.assign_successfully',
                ['module' => $this->moduleName]
            ));
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }
}
