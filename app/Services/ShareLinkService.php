<?php

namespace App\Services;

use App\Repositories\ShareLinkRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use App\Models\Sections;
use App\Models\User;
use App\Models\WorkspaceNotification;

use App\Notifications\ShareLinkEmailNotification;
use App\Models\ShareLinks;
use App\Enums\NotificationEnum;
use AWS\CRT\HTTP\Request;
use Carbon\Carbon;


/**
 * Class ShareLinkService
 * Service class for managing CRUD operations of shareLink
 * @package App\Services
 */
class ShareLinkService
{
    protected $notificationSlugs;
    /**
     * @var ShareLinkRepository Repository for interacting with the shareLink data
     */
    protected $shareLinkRepository;

    /**
     * ShareLinkService constructor.
     * @param ShareLinkRepository $shareLinkRepository The repository for interacting with shareLink data.
     */
    public function __construct(ShareLinkRepository $shareLinkRepository)
    {
        $this->shareLinkRepository = $shareLinkRepository;
        $this->notificationSlugs = NotificationEnum::Slugs->getAll();
    }

    /**
     * Create a new ShareLink.
     * @param array $shareLinkData The data for creating the shareLink.
     * @return Model The created shareLink data.
     */
    public function createShareLink(array $shareLinkData): Model
    {
        $now = now();

        // Set created_by from the authenticated user
        $shareLinkData['create_by'] = Auth::user()->id;

        // Handle password logic
        if (! empty($shareLinkData['is_password']) && $shareLinkData['is_password']) {
            if (empty($shareLinkData['s_password'])) {
                throw new \InvalidArgumentException('Password is required when "is_password" is true.');
            }
            $shareLinkData['s_password'] = Hash::make($shareLinkData['s_password']);
            // $shareLinkData['s_password'] = bcrypt($shareLinkData['s_password']);
        } else {
            $shareLinkData['s_password'] = null;
        }
        // Handle expiry logic
        if (! empty($shareLinkData['is_expired']) && $shareLinkData['is_expired']) {
            if (empty($shareLinkData['expiry_date'])) {
                throw new \InvalidArgumentException('Expiry date is required when "is_expired" is true.');
            }
        } else {
            $shareLinkData['expiry_date'] = null;
        }

        $result = $this->shareLinkRepository->create($shareLinkData);

        $assetIds = $this->shareLinkRepository->getAssetIds($shareLinkData);
        if (!empty($assetIds)) {
            $result->assets()->attach($assetIds, [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        if (! empty($shareLinkData['workspace_id'])) {
            $result->workspaces()->attach($shareLinkData['workspace_id']);
        }

        return $result;
    }

    /**
     * Get the query builder for shareLink.
     * @return Builder
     */
    public function getShareLinkQuery(): Builder
    {
        return $this->shareLinkRepository->query();
    }

    /**
     * Get the query Collection for shareLink.
     * @return LengthAwarePaginator
     */
    public function getShareLinkCollection($request): LengthAwarePaginator
    {
        return $this->shareLinkRepository->sharelinkWithWorkspace($request);
    }

    /**
     * Find an shareLink by their UUID.
     * @param int $shareLinkUuid The UUID of the shareLink.
     * @return Model|null The shareLink model or null if not found.
     */
    public function findByShareLinkId(int $Id, array $include): ?Model
    {
        return $this->shareLinkRepository->findById($Id, ['*'], $include);
    }

    /**
     * Find an shareLink by their URL.
     * @param int $shareLinkUuid The UUID of the shareLink.
     * @return Model|null The shareLink model or null if not found.
     */
    public function findByShareLinkURL(string $url)
    {
        return $this->shareLinkRepository->findByUrl($url);
    }

    /**
     * Update an existing shareLink.
     * @param int $shareLinkId The UUID of the shareLink to be updated.
     * @param array  $shareLinkId The data for updating the shareLink.
     * @return model True on successful update, false otherwise.
     */
    public function updateShareLink(int $shareLinkId, array $shareLinkData): Model
    {
        $now = now();
        // Handle password logic
        if (! empty($shareLinkData['is_password']) && $shareLinkData['is_password']) {
            if (empty($shareLinkData['s_password'])) {
                throw new \InvalidArgumentException('Password is required when "is_password" is true.');
            }
            $shareLinkData['s_password'] = Hash::make($shareLinkData['s_password']);
        } else {
            $shareLinkData['s_password'] = null;
        }

        // Handle expiry logic
        //dd($shareLinkData['is_expired']);
        if(!$shareLinkData['is_expired']){
            $shareLinkData['status'] = 1;
        }
        if (! empty($shareLinkData['is_expired']) && $shareLinkData['is_expired']) {
            if (empty($shareLinkData['expiry_date'])) {
                throw new \InvalidArgumentException('Expiry date is required when "is_expired" is true.');
            }
            $carbon = Carbon::parse($shareLinkData['expiry_date']);

            // Convert to UTC
            $utcDate = $carbon->setTimezone('UTC');
            $utcDate->setTime(23, 59, 0);

            // Output
            $utcDate->toDateTimeString();
            $shareLinkData['expiry_date']= $utcDate;
            $nowUTC = Carbon::now('UTC');
            if($shareLinkData['expiry_date'] < $nowUTC) {
                $shareLinkData['status'] = 0;
            }else{
                $shareLinkData['status'] = 1;
            }
        } else {
            $shareLinkData['expiry_date'] = null;
        }
        /* check if expired or not hten status chnages according */
        $result = $this->shareLinkRepository->update($shareLinkId, $shareLinkData);

        // Sync assets (if provided)
        if (array_key_exists('asset_id', $shareLinkData) && is_array($shareLinkData['asset_id'])) {
            $result->assets()->sync([]); // Remove existing
            foreach ($shareLinkData['asset_id'] as $assetId) {
                $result->assets()->attach($assetId, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (! empty($shareLinkData['section_id']) && is_array($shareLinkData['section_id'])) {
            // foreach ($assetIds as $assetId) {


            //     $result->assets()->attach($assetId, [
            //         'created_at' => $now,
            //         'updated_at' => $now,
            //     ]);
            // }
            $result->sections()->sync($shareLinkData['section_id']);
        }
        // if (! empty($shareLinkData['subfolder_id']) && is_array($shareLinkData['subfolder_id'])) {
        //     foreach ($assetIds as $assetId) {
        //         $result->assets()->attach($assetId, [
        //             'created_at' => $now,
        //             'updated_at' => $now,
        //         ]);
        //     }
        // }

        return $result;
    }

    /**
     * Deleting an existing shareLink.
     * @param int $shareLinkId The id of the shareLink to be deleted.
     * @return bool True on successful deletion, false otherwise.
     */
    public function deleteShareLinkById(int $shareLinkId): bool
    {
        return $this->shareLinkRepository->deleteById($shareLinkId);
    }
    /**
     * Retrieves the sections and subfolders that contain the selected assets.
     * @param int $Id The id of the share link.
     * @return Collection The filtered sections and subfolders.
     */
    public function getSelectedData(int $Id, object $request)
    {
        return $this->shareLinkRepository->getSelectedSharelinkData($Id, $request);
    }
    /**
     * Get counts of ShareLinks based on different criteria.
     *
     * @return array
     */
    public function getCounts($request): array
    {
        $slug = request()->input('slug');
        $userId = Auth::user()->id;

        return [
            'total'         =>  ShareLinks::with(['workspaces' => function ($query) use ($slug) {
                $query->where('slug', $slug);
            }])->whereHas('workspaces', function ($query) use ($slug) {
                $query->where('slug', $slug);
            })->count(),

            'active'        => ShareLinks::with(['workspaces' => function ($query) use ($slug) {
                $query->where('slug', $slug);
            }])->whereHas('workspaces', function ($query) use ($slug) {
                $query->where('slug', $slug);
            })->where('status', 1)->count(),

            'expired'       => ShareLinks::with(['workspaces' => function ($query) use ($slug) {
                $query->where('slug', $slug);
            }])->whereHas('workspaces', function ($query) use ($slug) {
                $query->where('slug', $slug);
            })->where('status', 0)->count(),

            'created_by_me' => ShareLinks::with(['workspaces' => function ($query) use ($slug) {
                $query->where('slug', $slug);
            }])->whereHas('workspaces', function ($query) use ($slug) {
                $query->where('slug', $slug);
            })->where('create_by', $userId)->count(),
        ];
    }

    public function getUser($request)
    {
        $slug = $request->input('slug');
        $userIds =  Sharelinks::whereHas('workspaces', function ($query) use ($slug) {
            $query->where('slug', $slug);
        })
            ->distinct()
            ->pluck('create_by');
        $users = User::whereIn('id', $userIds)->select('id', 'email')->get()->toArray();
        return $users;
    }

    /**
     * Create a new ShareLink.
     * @param array $shareLinkData The data for creating the shareLink.
     * @return Model The created shareLink data.
     */
    public function removeShareLinkAsset(int $id, array $shareLinkData): Model
    {
        $result = $this->findByShareLinkId($id, ['assets']);
        $assetIds = $this->shareLinkRepository->getAssetIds($shareLinkData);
        if (!empty($assetIds)) {
            $result->assets()->detach($assetIds);
        }

        // Attach assets (multiple)

        if (! empty($shareLinkData['section_id']) && is_array($shareLinkData['section_id'])) {
            $result->sections()->detach($shareLinkData['section_id']);
        }


        return $result;
    }

    /**
     * Send notifications to users that are subscribed to the given collection.
     *
     * @param Collection $collection The collection to send notifications for.
     *
     * @return Collection A collection of sent notifications with their associated user.
     */
    public function notifyUserForShareLinkView($sharelink, $request)
    {
        $user = User::find($sharelink->create_by);
        if (! $user) {
            return null;
        }
        $loginUser = Auth::user()??null;
        $loginRole = false;
        if($loginUser){
            $loginRole = $loginUser->hasRole('Super Admin');
        }
        $loginUserId = Auth::user()->id??null;
        /* check if superadmin itself and owner of sharelink itself view then not end notification */
        if ($sharelink->create_by == $loginUserId || $loginRole) {
                return null;
        }
        // Send in-app notification if enabled
        if (!$sharelink->is_private) {
            $requestUser = $request->input('email');
        } else {
            $requestUser = Auth::user()->email;
        }

        $title = "Share Link Viewed";

        $message = "{$requestUser}  viewed Sharelink - {$sharelink->name}";
        $type="share_link_viewed";
        $url = $sharelink->url;
        $url = stripslashes($url);
        $superadmins = User::whereHas('roles', function ($query) {
            $query->where('name', 'Super Admin');
        })->get();
        if (!($user && $user->hasRole('Super Admin'))) {
            if ($sharelink->is_notify && $request->has('is_log')) {
                    $this->shareLinkRepository->notifyUser($user,
                    $title,
                    $message,
                    $url,
                    $type,
                    $requestUser,
                    false,
                    true,
                    $sharelink->name);
            }
        }
     
        foreach ($superadmins as $superUser) {
             //owner of sharelink itself view sharelink the not receive notification
            
                if ($request->has('is_log')) {
                $this->shareLinkRepository->notifyUser($superUser,
                $title,
                $message,
                $url,
                $type,
                $requestUser,
                true,
                true,
                $sharelink->name);
                }
        }
        return $sharelink;
    }

    /**
     * Send notifications to users that are subscribed to the given collection.
     *
     * @param Collection $collection The collection to send notifications for.
     *
     * @return Collection A collection of sent notifications with their associated user.
     */
    public function notifyUserForAssetRemove($sharelink)
    {
        $user = User::find($sharelink->create_by);
        if (! $user) {
            return null;
        }
        // Send in-app notification if enabled
        $loginUser = Auth::user();
        $title = "Asset Removed from share link";
        $message = $loginUser->name . " removed an asset from Sharelink - {$sharelink->name}";
        $type = "asset_removed_from_share_link";
        $url = $sharelink->url;
       // $url = json_encode($url, JSON_UNESCAPED_SLASHES);
        if (!($user && $user->hasRole('Super Admin'))) {
            if ($sharelink->is_notify) {
                $this->shareLinkRepository->notifyUser($user,
                $title,
                $message,
                $url,
                $type,
                $loginUser->name,
                true,
                true,
                $sharelink->name);
            }
        }

        $superadmins = User::whereHas('roles', function ($query) {
            $query->where('name', 'Super Admin');
        })->get();
        foreach ($superadmins as $superUser) {
            $this->shareLinkRepository->notifyUser($superUser,
            $title,
            $message,
            $url,
            $type,
            $loginUser->name,
            true,
            true,
            $sharelink->name);
        }
        return $sharelink;
    }
/**
 * Retrieve the real IP address of the user.
 * 
 * This function checks for the 'HTTP_X_FORWARDED_FOR' header,
 * which is often set by proxies to provide the original client IP address.
 * If multiple IPs are present, it returns the first one.
 * If not available, it checks for 'HTTP_CLIENT_IP'.
 * As a fallback, it returns the IP address directly from the request.
 *
 * @return string The real IP address of the user.
 */

      public function getRealUserIp()
    {
        if (request()->server('HTTP_X_FORWARDED_FOR')) {
            $ips = explode(',', request()->server('HTTP_X_FORWARDED_FOR'));
            return trim($ips[0]); // Return the first IP (client IP)
        }

        if (request()->server('HTTP_CLIENT_IP')) {
            return request()->server('HTTP_CLIENT_IP');
        }

        return request()->ip(); // fallback
    }

}

