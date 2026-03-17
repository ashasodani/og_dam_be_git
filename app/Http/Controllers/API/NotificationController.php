<?php

namespace App\Http\Controllers\API;

use App\Enums\NotificationEnum;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Requests\Notification\WorkspaceNotificationRequest;
use App\Http\Resources\WorkspaceCreateNotificationResource;
use App\Http\Resources\WorkspaceNotificationResource;
use App\Services\NotificationService;
use App\Services\WorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class NotificationController extends BaseController
{
    protected $notificationSlugs;
    /**
     * @var WorkspaceService The service for handling section operations.
     */
    protected $workspaceService;
    /**
     * @var NotificationService The service for handling admin role operations.
     */
    protected $notificationService;

    /**
     * NotificationController constructor.
     *
     * @param NotificationService       $notificationService         The service for handling admin role operations
     */
    public function __construct(NotificationService $notificationService, WorkspaceService $workspaceService)
    {
        $this->notificationService = $notificationService;
        $this->workspaceService    = $workspaceService;

        $this->moduleName        = trans('notification.workspace_notification');
        $this->notificationSlugs = NotificationEnum::Slugs->getAll();
    }

    /**
     * Display a listing of admin roles.
     *
     * @return View|RedirectResponse
     */

    public function getWorkspaceNotification(Request $request): mixed
    {
        try {
            $workspaceData = $this->notificationService->getWorkspaceCollection($request);

            return $this->successResponse(
                WorkspaceNotificationResource::collection($workspaceData),
                trans('common.fetch_successfully', ['module' => $this->moduleName])
            );
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Store a newly created workspace notification in storage.
     *
     * @param WorkspaceNotificationRequest $request
     * @return JsonResponse
     */
    public function workspaceNotificationStore(WorkspaceNotificationRequest $request): JsonResponse
    {
        try {
            $requestData   = $request->all();
            $notifications = $this->notificationService->createWorkspaceNotification($requestData);

            return $this->successResponse(
                new WorkspaceCreateNotificationResource($notifications),
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
     * Display in app user notification
     *
     * @return
     */

    public function getNotification(Request $request): mixed
    {
        try {
            $append = $request->all();
            //dd($append);
            $append['per_page'] = $append['per_page'] ?? 10000;
            if ($request->has('type')) {
                $type       = $request->type;
                $notifyData = $notification = Auth::user()->notifications()->orderBy('created_at', 'desc')->where('notifiable_id', Auth::user()->id)
                    ->whereRaw("(data::jsonb ->> 'type') = ?", [$type])
                    ->paginate($append['per_page']);
            } else {
                $notifyData = Auth::user()->notifications()->orderBy('created_at', 'desc')->paginate($append['per_page']);
            }

            return $this->successResponse(
                $notifyData,
                trans('common.fetch_successfully', ['module' => $this->moduleName])
            );
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    public function getFilter(Request $request): mixed
    {
        try {
            $filterdata = [
                [
                    'type' => 'share_link_viewed',
                    'name'  => $this->notificationSlugs["sharelinks"]["sharelink_asset_view"]
                ],
                [
                    'type' => 'sharelink_asset_download',
                    'name'  => $this->notificationSlugs["sharelinks"]["sharelink_asset_download"]
                ],
                [
                    'type' => 'collection_viewed',
                    'name'  => $this->notificationSlugs["collection"]["collection_view"]
                ],
                [
                    'type' => 'asset_add_to_collection',
                    'name'  => $this->notificationSlugs["collection"]["collection_asset_add"]
                ],
                [
                    'type' => 'invitation_accept',
                    'name'  => $this->notificationSlugs["users"]["invitation_accept"]
                ],
                [
                    'type' => 'asset_update',
                    'name'  => "Asset updated"
                ],
                [
                    'type' => 'asset_add_to_share_link',
                    'name'  => "Asset added to share link"
                ],
                [
                    'type' => 'asset_removed_from_share_link',
                    'name'  => "Asset removed from share link"
                ],
                [
                    'type' => 'share_link_expired',
                    'name'  => "Share link expired"
                ],
            ];
            return $this->successResponse(
                $filterdata,
                trans('common.fetch_successfully', ['module' => $this->moduleName])
            );
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        //  dd($notification);
        if ($notification->read_at === null) {
            $notification->markAsRead();
        }
        $unreadCount = Auth::user()->unreadNotifications()->count();
        $notification->unreadCount = $unreadCount;
      
        return $this->successResponse(
            $notification,
            trans('common.update_successfully', ['module' => $this->moduleName])
        );
    }

    /**
     * Get read and unread notification counts for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getNotificationCounts(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            // Count unread notifications
            $unreadCount = $user->unreadNotifications()->count();

            // Count read notifications
            $readCount = $user->readNotifications()->count();

            // Or total notifications
            $totalCount = $user->notifications()->count();

            $data = [
                'read_count'   => $readCount,
                'unread_count' => $unreadCount,
                'total_count'  => $totalCount,
            ];

            return $this->successResponse(
                $data,
                trans('common.fetch_successfully', ['module' => 'Notification Counts'])
            );
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }
}
