<?php
namespace App\Http\Controllers\API;

use App\Enums\PermissionEnum;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Requests\Profile\ProfileUpdateRequest;
use App\Http\Requests\UserManagement\AssignCreateRequest;
use App\Http\Requests\UserManagement\AssignUpdateRequest;
use App\Http\Resources\BaseCollection;
use App\Http\Resources\InviteUserResource;
use App\Http\Resources\UserResource;use App\Models\User;
use App\Services\UserInvitationService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class UserController extends BaseController
{
    /**
     * @var UserInvitationService The service for handling section operations.
     */

    protected $userInvitationService;
    /**
     * @var UserService The service for handling section operations.
     */

    protected $userService;

    /**
     * @var permissionSlugs The slug for handling section operations.
     */
    protected $permissionSlugs;

    /**
     * WorkspaceController constructor
     *
     * @param UserService   $userService   The service for handling Workspace related operations.
     */
    public function __construct(UserService $userService, UserInvitationService $userInvitationService)
    {
        $this->userService           = $userService;
        $this->userInvitationService = $userInvitationService;
        $this->moduleName            = trans("user.module_name");
        $this->permissionSlugs       = PermissionEnum::Slugs->getAll();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignResource(AssignCreateRequest $request): JsonResponse
    {
        try {
            $input = $request->all();
            $data  = $this->userService->asignUserResources($input);

            return $this->successResponse($data,
                trans(
                    'common.invite_successfully',
                    ['module' => $this->moduleName]
                ));
        } catch (\Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * get a newly Invited resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAssignedResource(Request $request): JsonResponse
    {
        $this->authorize($this->permissionSlugs["user"]["list"], User::class);

        $include = $request->get('include') ? [$request->get('include')] : [];
        $status  = $request->get('status');
        try {
            if ($status === 'true') {
                $result = $this->userService->getUserResourceCollection($request);
                return $this->successResponse(
                    new BaseCollection($result, UserResource::class),
                    trans(
                        'common.fetch_successfully',
                        ['module' => $this->moduleName]
                    ));

            } else {
                $result = $this->userInvitationService->getInvitedUserCollection($request);
                return $this->successResponse(
                    new BaseCollection($result, InviteUserResource::class),
                    trans(
                        'common.fetch_successfully',
                        ['module' => $this->moduleName]
                    ));
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
    public function updateAssignResource(int $id, AssignUpdateRequest $request): JsonResponse
    {
        $this->authorize($this->permissionSlugs["user"]["update"], User::class);

        try {
            $input = $request->all();
            $data  = $this->userService->updateUserResources($id, $input);

            // $include = $request->get('include') ? array($request->get('include')) : [];
            // $result = $this->userService->getInvitedUserCollection($data, $include);
            return $this->successResponse($data,
                trans(
                    'common.update_successfully',
                    ['module' => $this->moduleName]
                ));
        } catch (\Throwable $throwable) {
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
    public function destroy(Request $request, int $userId): Mixed
    {
        $this->authorize($this->permissionSlugs["user"]["delete"], User::class);

        try {
            $users = $this->userService->deleteUser($userId);
            if ($users) {
                return $this->successResponse([],
                    trans(
                        'common.delete_successfully',
                        ['module' => $this->moduleName]
                    ));
            }
            return $this->sendError('Something Wrong.', trans('common.something_wrong'), 401);
        } catch (\Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    // public function destroy(Request $request, int $userId): mixed
    // {
    //     // $this->authorize($this->permissionSlugs["user"]["delete"], User::class);

    //     try {
    //         $users = $this->userService->deactivateUser($userId);
    //         if ($users) {
    //             return $this->successResponse(
    //                 new UserResource($users),
    //                 trans('common.deactivate_successfully', ['module' => $this->moduleName])
    //             );
    //         }
    //         return $this->sendError('Something went wrong.', trans('common.something_wrong'), 401);
    //     } catch (\Throwable $throwable) {
    //         report($throwable);
    //         return response()->json(['error' => $throwable->getMessage()], 500);
    //     }
    // }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($Id, Request $request): mixed
    {
        try {
            $userId = Auth::user()->id;

            $data = $this->userService->getProfileDetails($userId);
            return $this->successResponse(['data' => $data],
                trans(
                    'common.fetch_successfully',
                    ['module' => $this->moduleName . ' Profile']
                ));
        } catch (\Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Update the specified user profile in storage.
     *
     * @param int $userId The id of the user to update.
     * @param ProfileUpdateRequest $editUserRequest The request containing the validated data for updating the user profile.
     *
     * @return JsonResponse
     */
    public function update(int $userId, ProfileUpdateRequest $editUserRequest): JsonResponse
    {
        try {
            $data   = $editUserRequest->validated();
            $userId = Auth::user()->id;

            $user = $this->userService->updateProfileDetails($userId, $data);

            return $this->successResponse(
                new UserResource($user),
                trans(
                    'common.update_successfully',
                    ['module' => $this->moduleName . ' Profile']
                ));
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    public function getMemberDetail($id,Request $request){
        try {
            $userId = $id;

            $data = $this->userService->getProfileDetails($userId);
            return $this->successResponse(['data' => $data],
                trans(
                    'common.fetch_successfully',
                    ['module' => $this->moduleName . ' Profile']
                ));
        } catch (\Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    public function updateMemberStatus($id, Request $request)
    {
        try {
            $user = User::find($id);
            if ($user && $request->has('status')) {
                $user->update(['status' => $request->status]);
            }

            $data = $this->userService->getProfileDetails($id);

            return $this->successResponse(['data' => $data],
                trans(
                    'common.update_successfully',
                    ['module' => $this->moduleName . ' Status']
                ));
        } catch (\Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }
}
