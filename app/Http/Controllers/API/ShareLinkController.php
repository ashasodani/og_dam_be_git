<?php
namespace App\Http\Controllers\API;

use App\Enums\PermissionEnum;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Requests\ShareLink\ShareLinkCreateRequest;
use App\Http\Requests\ShareLink\ShareLinkInfoRequest;
use App\Http\Requests\ShareLink\ShareLinkRemoveRequest;
use App\Http\Requests\ShareLink\ShareLinkUpdateRequest;
use App\Http\Resources\BaseCollection;
use App\Http\Resources\ShareLinkGuestResource;
use App\Http\Resources\SharelinkLogResource;
use App\Http\Resources\ShareLinkResource;
use App\Models\SharelinkLogs;
use App\Models\ShareLinks;
use App\Services\ShareLinkService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Throwable;
use Illuminate\Support\Facades\Auth;

class ShareLinkController extends BaseController
{
    /**
     * @var ShareLinkService The service for handling section operations.
     */
    protected $shareLinkService;

    /**
     * @var permissionSlugs The slug for handling section operations.
     */
    protected $permissionSlugs;

    /**
     * ShareLinkController constructor
     *
     * @param ShareLinkService   $shareLinkService   The service for handling ShareLink related operations.
     */
    public function __construct(ShareLinkService $shareLinkService)
    {
        $this->shareLinkService = $shareLinkService;
        $this->moduleName       = trans("sharelink.module_name");
        $this->permissionSlugs  = PermissionEnum::Slugs->getAll();
        //$this->authorizeResource(ShareLinks::class);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Mixed
     */
    public function index(Request $request): mixed
    {
        $this->authorize($this->permissionSlugs["share_links"]["list"], ShareLinks::class);
        $shareLinkData = $this->shareLinkService->getShareLinkCollection($request);
        // dd($shareLinkData);
        try {
            return $this->successResponse(
                new BaseCollection($shareLinkData, ShareLinkResource::class),
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
    public function store(ShareLinkCreateRequest $request, ShareLinks $model): JsonResponse
    {
        $this->authorize($this->permissionSlugs["share_links"]["create"], ShareLinks::class);
        try {
            $input     = $request->all();
            $shareLink = $this->shareLinkService->createShareLink($input);
            return $this->successResponse(
                new ShareLinkResource($shareLink),
                trans(
                    'common.create_successfully',
                    ['module' => $this->moduleName]
                )
            );
        } catch (\Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Display the specified ShareLink.
     *
     * @param int $shareLinkId The ID of the ShareLink to view.
     *
     * @return Mixed
     */
    public function show(int $Id, Request $request): mixed
    {
        $this->authorize($this->permissionSlugs["share_links"]["list"], ShareLinks::class);

        try {
            $include   = $request->get('include') ? [$request->get('include')] : [];
            $shareLink = $this->shareLinkService->findByShareLinkId($Id, $include);
            return $this->successResponse(
                new ShareLinkResource($shareLink),
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
     * Update the ShareLink in storage.
     *
     * @param int  $id Uuid of the shareLink.
     * @param ShareLinkUpdateRequest $request The request containing the validated data for updating shareLink.
     *
     * @return JsonResponse
     */
    public function update(int $shareLinkId, ShareLinkUpdateRequest $shareLinkRequest): JsonResponse
    {
        $this->authorize($this->permissionSlugs["share_links"]["update"], ShareLinks::class);
        try {
            $data      = $shareLinkRequest->validated();
            $shareLink = $this->shareLinkService->updateShareLink($shareLinkId, $data);

            return $this->successResponse(
                new ShareLinkResource($shareLink),
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
     * Remove the specified ShareLink from storage.
     *
     * @param Request $request The request object.
     * @param int  $shareLinkid id of the shareLink.
     *
     * @return Mixed
     */
    public function destroy(Request $request, int $shareLinkId): Mixed
    {
        $this->authorize($this->permissionSlugs["share_links"]["delete"], ShareLinks::class);

        try {
            $shareLink = $this->shareLinkService->deleteShareLinkById($shareLinkId);
            if ($shareLink) {
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
        }
    }

    /**
     * Sharelink Info
     *
     * @param string  hsrelink url
     *
     *
     * @return JsonResponse
     */
    public function shareLinkInfo(ShareLinkInfoRequest $request): JsonResponse
    {
        try {
            $shareLink = $this->shareLinkService->findByShareLinkUrl($request['sharelink_url']);
            if ($shareLink->expiry_date) {
                $timezone   = 'UTC';
                $expiry = Carbon::parse($shareLink->expiry_date,$timezone);
                if ($expiry->isPast()) {
                    return $this->sendValidation([], 'Your link has expired or is no longer valid. Please request a new link.', 422);
                }
            }

            return $this->successResponse(
                new ShareLinkResource($shareLink),
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
     * Sharelink Info
     *
     * @param string  hsrelink url
     *
     *
     * @return JsonResponse
     */
    public function shareLinkGuestInfo(int $Id, Request $request): JsonResponse
    {
        try {
            $include   = $request->get('include') ? [$request->get('include')] : ['assets', 'sections'];
            $shareLink = $this->shareLinkService->findByShareLinkId($Id, $include);
            if(!$shareLink){
                return $this->sendError('Sharelink not found.',  trans(
                    'common.link_not_found',
                    ['module' => $this->moduleName]
                ), 404);
            }
            $email     = null;
            if ($shareLink->is_private) {
                // Manually run auth middleware
                $this->middleware('auth:sanctum');

                // Or manually check auth
                if (! $request->user()) {
                    return response()->json([
                        'success'    => false,
                        'message'    => 'You are not authorized to perform this actions',
                        'errors'     => 'You are not authorized to perform this actions',
                        'error_code' => 403,
                    ], 403);
                }
                $email = $request->user()->email;
            }
            if ($shareLink->is_email_address) {
                $validator = Validator::make($request->all(), [
                    'email' => [
                        'required',
                        'email',
                        'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
                        function ($attribute, $value, $fail) {
                            $testEmails = ['test@example.com', 'test@domain.com', 'demo@example.com'];
                            if (in_array(strtolower($value), $testEmails)) {
                                $fail('The ' . $attribute . ' is not allowed.');
                            }
                        },
                    ],
                ], [
                    'email.required' => 'Email is required.',
                    'email.email'    => 'Email must be a valid email address.',
                    'email.exists'   => 'Email is not registered.',
                ]);
                if ($validator->fails()) {
                    return $this->sendValidation([], $validator->errors(), 422);
                }
                $email = $request->email;
            }
            if (Auth::check()) {
                $user = Auth::user();
                if ($user->hasRole('Super Admin') || $user->id === $shareLink->create_by) {
                    $isPassword = false;
                } else {
                    $isPassword = $shareLink->is_password ? true : false;
                }
            } else {
                $isPassword = $shareLink->is_password ? true : false;
            }
            if ($isPassword) {
                $validator = Validator::make($request->all(), [
                    'password' => 'required|string',
                ], [
                    'password.required' => 'Password is required.',
                ]);
                if ($validator->fails()) {
                    return $this->sendValidation([], $validator->errors(), 422);
                }
                if (! Hash::check($request->password, $shareLink->s_password)) {
                    return $this->sendValidation([], 'Invalid Password', 422);
                }
                $email = null;
            }

            /* Add log in sharelink log  table */
            if($request->has('is_log')){
                
                    SharelinkLogs::create([
                        'share_link_id' => $shareLink->id,
                        'email'         => $email,
                        'asset_id'      => $shareLink->id,
                        'ip_address'    => $this->shareLinkService->getRealUserIp(),
                        'is_login_user' => $request->user() ? true : false,
                        'user_agent'    => $request->header('User-Agent'),
                    ]);
                
            }
            /* Add log in sharelink log  table */

            $filteredSections = $this->shareLinkService->getSelectedData($Id, $request);

            $shareLink->selectedData = $filteredSections;
            /* noitification send when user view share link start */
            // $authHeader = $request->header('Authorization');
            // if (! $authHeader) {
                $this->shareLinkService->notifyUserForShareLinkView($shareLink,$request);
            //}
            /* noitification send when user view share link end */

            return $this->successResponse(
                new ShareLinkGuestResource($shareLink),
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
     * Get ShareLink counts.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function counts(Request $request): JsonResponse
    {
        try {
            $counts = $this->shareLinkService->getCounts($request);

            return $this->successResponse(
                $counts,
                trans('common.fetch_successfully', ['module' => $this->moduleName . ' Counts'])
            );
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
    public function removeAssets(int $Id, ShareLinkRemoveRequest $request): JsonResponse
    {
        //$this->authorize($this->permissionSlugs["share_links"]["create"], ShareLinks::class);
        try {
            $input      = $request->all();
            $shareLink  = $this->shareLinkService->removeShareLinkAsset($Id, $input);
            $notifyUser = $this->shareLinkService->notifyUserForAssetRemove($shareLink);
            return $this->successResponse(
                $input,
                trans(
                    'common.delete_successfully',
                    ['module' => $this->moduleName]
                )
            );
        } catch (\Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Display the specified Share link log.
     *
     * @param int $id
     *
     * @return JsonResponse
     */
    public function viewShareLinkLog(int $id,Request $request): JsonResponse
    {
        try {
            $perPage = request()->input('per_page', 10);
            $log = SharelinkLogs::where('share_link_id', $id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

            return $this->successResponse(
                new BaseCollection($log, ShareLinkLogResource::class),
                trans(
                    'common.fetch_successfully',
                    ['module' => $this->moduleName]
                )
            );
        } catch (\Throwable $throwable) {
            report($throwable);

            return response()->json([
                'success'    => false,
                'message'    => trans('common.fetch_error', ['module' => $this->moduleName]),
                'error'      => $throwable->getMessage(),
                'error_code' => 500,
            ], 500);
        }
    }
     /**
     * Display the specified Share link log.
     *
     * @param int $id
     *
     * @return JsonResponse
     */
    public function createdUser(Request $request)
    {
        try {
            $users = $this->shareLinkService->getUser($request);

            return $this->successResponse(
                $users,
                trans('common.fetch_successfully', ['module' => $this->moduleName . ' Counts'])
            );
        } catch (\Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }
}
