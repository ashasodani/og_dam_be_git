<?php
namespace App\Http\Controllers\API;

use App\Enums\PermissionEnum;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Requests\Invite\InviteCreateRequest;
use App\Http\Requests\Invite\ResendInviteRequest;
use App\Http\Resources\InviteUserResource;
use App\Models\InviteUsers;
use App\Models\User;
use App\Services\UserInvitationService;
use Illuminate\Http\JsonResponse;use Illuminate\Http\Request;use Throwable;

class UserInvitationController extends BaseController
{
    /**
     * @var UserInvitationService The service for handling section operations.
     */
    protected $userInvitationService;

    /**
     * @var permissionSlugs The slug for handling section operations.
     */
    protected $permissionSlugs;

    /**
     * WorkspaceController constructor
     *
     * @param UserService   $userService   The service for handling Workspace related operations.
     */
    public function __construct(UserInvitationService $userInvitationService)
    {
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

    public function store(InviteCreateRequest $request): JsonResponse
    {
        $this->authorize($this->permissionSlugs["user"]["create"], User::class);

        try {
            $input = $request->all();
            $data  = $this->userInvitationService->inviteUser($input);

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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function resendInvitation(ResendInviteRequest $request): JsonResponse
    {
         $this->authorize($this->permissionSlugs["user"]["create"], User::class);

        try {
            $input = $request->all();
            $data  = $this->userInvitationService->resendInviteUser($input);
            // $result = $this->userInvitationService->getInvitedUserCollection($data, $include);
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

    public function index(Request $request): JsonResponse
    {
        $this->authorize($this->permissionSlugs["user"]["list"], User::class);

        $include = $request->get('include') ? [$request->get('include')] : [];

        $result = $this->userInvitationService->getInvitedUserCollection($request, $include);
        try {
            return $this->successResponse(
                InviteUserResource::collection($result),
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
     * Remove the specified section from storage.
     *
     * @param Request $request The request object.
     * @param int  $section id of the section.
     *
     * @return Mixed
     */

    public function destroy(Request $request, int $invitationId): Mixed
    {
        $this->authorize($this->permissionSlugs["user"]["delete"], User::class);

        try {
            $invitedUsers = $this->userInvitationService->deleteInvitedUser($invitationId);
            if ($invitedUsers) {
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
}
