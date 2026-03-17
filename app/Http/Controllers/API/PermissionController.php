<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Services\RoleService;
use App\Services\PermissionService;
use Throwable;

class PermissionController extends BaseController
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
     * RoleController constructor.
     *
     * @param RoleService       $roleService         The service for handling admin role operations.
     * @param PermissionService $permissionService   The service for handling permission operations.
     */
    public function __construct(
        RoleService $roleService,
    ) {
        $this->roleService = $roleService;
        $this->moduleName = trans('permission.module_name');
    }


   
     /**
     * @purpose assign role to  user
     * @param Request $request
     * @return string \Illuminate\Http\Response
     */
    public function assignPermission(Request $request)
    {
        try {
            $permissionArray = explode(",", $request->get('permission_id'));
            $role = $this->roleService->syncPermissions($request->role_id, $permissionArray);
            return $this->successResponse([$role->name],  trans(
                'common.assign_successfully',
                ['module' =>  $this->moduleName]
            ));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Role not found.'], 404);
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }
}