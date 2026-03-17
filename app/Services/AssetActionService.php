<?php
namespace App\Services;

use App\Models\Assets;
use App\Models\Collections;
use App\Models\Sections;
use App\Models\SubFolders;
use App\Models\Tags;
use App\Models\User;
use App\Models\WorkspaceNotification;
use App\Notifications\ShareLinkAppNotification;
use App\Repositories\AssetMetaRepository;
use App\Repositories\AssetRepository;
use App\Repositories\AttachmentRepository;
use App\Repositories\CollectionRepository;
use App\Repositories\LabelRepository;
use App\Repositories\SectionRepository;
use App\Repositories\ShareLinkRepository;
use App\Repositories\SubFolderRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Class AssetService
 * Service class for managing CRUD operations of Asset
 * @package App\Services
 */
class AssetActionService
{
    /**
     * @var AssetRepository Repository for interacting with the Asset data
     */
    protected $assetRepository;

    /**
     * @var SectionRepository Repository for interacting with the section data
     */
    protected $sectionRepository;

    /**
     * @var AttachmentRepository Repository for interacting with the Asset data
     */
    protected $subfolderRepository;

    /**
     * @var AssetMetaRepository Repository for interacting with the Asset data
     */
    protected $assetMetaRepository;

    /**
     * @var LabelRepository Repository for interacting with the Asset data
     */
    protected $labelRepository;
    /**
     * @var shareLinkRepository Repository for interacting with the Asset data
     */
    protected $shareLinkRepository;

    /**
     * @var shareLinkRepository Repository for interacting with the Asset data
     */
    protected $collectionRepository;
    /**
     * AssetService constructor.
     * @param AssetRepository $assetRepository The repository for interacting with Asset data.
     */
    public function __construct(AssetRepository $assetRepository, AssetMetaRepository $assetMetaRepository, SectionRepository $sectionRepository, SubFolderRepository $subfolderRepository, LabelRepository $labelRepository, CollectionRepository $collectionRepository, ShareLinkRepository $shareLinkRepository)
    {
        $this->assetRepository      = $assetRepository;
        $this->sectionRepository    = $sectionRepository;
        $this->subfolderRepository  = $subfolderRepository;
        $this->assetMetaRepository  = $assetMetaRepository;
        $this->labelRepository      = $labelRepository;
        $this->shareLinkRepository  = $shareLinkRepository;
        $this->collectionRepository = $collectionRepository;
    }

    /**
     * Get the query builder for Asset.
     * @return Builder
     */
    public function getAssetQuery(): Builder
    {
        return $this->assetRepository->query();
    }

    /**
     * Get the query Collection for Asset.
     * @return Collection
     */
    public function getAssetCollection($request): Collection
    {
        return $this->assetRepository->getAssetData($request);
    }

    /**
     * Find an Asset by their ID.
     * @param int $AssetID The ID of the workspace.
     * @return Model|null The workspace model or null if not found.
     */
    public function findByAssetId(int $Id, object $request): ?Model
    {
        $include = $request->has('include') ? json_decode($request->get('include')) : [];
        return $this->assetRepository->findById($Id, ['*'], $include);
    }

    /**
     * Deleting an existing Asset.
     * @param array $assetId The id of the workspace to be deleted.
     * @return Mixed True on successful deletion, false otherwise.
     */
    public function bulkAssetDelete(array $data): Mixed
    {
        //  dd($data['asset_id']);
        $Asset = Assets::whereIn('id', $data['asset_id'])->get();
        foreach ($Asset as $asses) {
            $asses->sections()->detach();
            $asses->subfolders()->detach();
            $asses->delete();
        }
        if(!empty($data['section_id']) && is_array($data['section_id'])){
            $sections = Sections::whereIn('id', $data['section_id'])->get();
            foreach ($sections as $section) {
                $section->delete();
            }
        }
        if(!empty($data['subfolder_id']) && is_array($data['subfolder_id'])){
            $subfolders = Subfolders::whereIn('id', $data['subfolder_id'])->get();
            foreach ($subfolders as $subfolder) {
                $subfolder->delete();
            }
        }

        return $Asset;

    }

    /**
     * Move the given Assets to the given section.
     *
     * @param int $sectionId The ID of the section to move the Assets to.
     * @param array $AssetIds The IDs of the Assets to be moved.
     *
     * @return Model The section model with the Assets associated.
     */

    public function moveAssetWithLocations(array $data): Model | Collection
    {
        $assetIds = Arr::wrap($data['asset_id'] ?? []);
        // if (empty($assetIds)) {
        //     throw new \Exception("No asset IDs provided.");
        // }
        $subfolderIds = Arr::wrap($data['subfolder_id'] ?? []);

        // Normalize all IDs to arrays
        $fromSectionIds   = Arr::wrap($data['from_section_id'] ?? []);
        $toSectionIds     = Arr::wrap($data['to_section_id'] ?? []);
        $fromSubfolderIds = Arr::wrap($data['from_subfolder_id'] ?? []);
        $toSubfolderIds   = Arr::wrap($data['to_subfolder_id'] ?? []);

        // Fetch all source and destination models
        $fromSections   = ! empty($fromSectionIds) ? $this->sectionRepository->findMany($fromSectionIds) : collect();
        $toSections     = ! empty($toSectionIds) ? $this->sectionRepository->findMany($toSectionIds) : collect();
        $fromSubfolders = ! empty($fromSubfolderIds) ? $this->subfolderRepository->findMany($fromSubfolderIds) : collect();
        $toSubfolders   = ! empty($toSubfolderIds) ? $this->subfolderRepository->findMany($toSubfolderIds) : collect();

        // Loop through each asset
        foreach ($assetIds as $assetId) {
            // Detach from all from-sections
            foreach ($fromSections as $fromSection) {
                $fromSection->assets()->detach($assetId);
            }

            // Detach from all from-subfolders
            foreach ($fromSubfolders as $fromSubfolder) {
                $fromSubfolder->assets()->detach($assetId);
            }

            // Attach to all to-sections
            foreach ($toSections as $toSection) {
                $toSection->assets()->syncWithoutDetaching([$assetId]);
            }

            // Attach to all to-subfolders
            foreach ($toSubfolders as $toSubfolder) {
                $toSubfolder->assets()->syncWithoutDetaching([$assetId]);
            }
        }
        /* loop throu each subfolder */
        foreach ($subfolderIds as $subfolderId) {
            foreach ($fromSections as $fromSection) {
                $fromSection->subfolders()->detach($subfolderIds);
            }
             foreach ($toSections as $toSection) {
                $toSection->subfolders()->syncWithoutDetaching($subfolderIds);
            }

        }

        // Return the first non-empty target model or empty collection
        // return $toSections->first() ?? $toSubfolders->first() ?? collect();

        return $toSections->first() ?: $toSubfolders->first() ?: collect();
    }

    /**
     * Copy the given Assets to the given section.
     *
     * @param int $sectionId The ID of the section to copy the Assets to.
     * @param array $AssetIds The IDs of the Assets to be copied.
     *
     * @return Model The section model with the Assets associated.
     */
    public function copyAssetWithLocations($data): Model
    {
        $toSectionData   = $this->sectionRepository->findById($data['to_section_id'], ['*'], []);
        $toSubFolderData = $this->subfolderRepository->findById($data['to_subfolder_id'], ['*'], []);

        foreach ($data['asset_id'] as $assetData) {

            $toSectionData->assets()->syncWithoutDetaching([$assetData]);

            $toSubFolderData->assets()->syncWithoutDetaching([$assetData]);
        }
        return $toSectionData;
    }

    /**
     * Merge the given Assets to the given section.
     *
     * @param int $sectionId The ID of the section to Merge the Assets to.
     * @param array $AssetIds The IDs of the Assets to be copied.
     *
     * @return Model The section model with the Assets associated.
     */
    public function mergeAssetWithLocations($data): Model
    {
        $toSectionData   = $this->sectionRepository->findById($data['to_section_id'], ['*'], []);
        $toSubFolderData = $this->subfolderRepository->findById($data['to_subfolder_id'], ['*'], []);

        foreach ($data['asset_id'] as $assetData) {
            $toSectionData->assets()->syncWithoutDetaching([$assetData]);

            $toSubFolderData->assets()->syncWithoutDetaching([$assetData]);
        }
        return $toSectionData;
    }

    public function assignAssetsWithLabel($data): Model
    {
        $toLabelData = $this->labelRepository->findById($data['label_id'], ['*'], []);

        foreach ($data['asset_id'] as $assetData) {

            $toLabelData->assets()->syncWithoutDetaching([$assetData]);
        }
        return $toLabelData;
    }
    /**
     * Copy the given Assets to the given section.
     *
     * @param int $sectionId The ID of the section to copy the Assets to.
     * @param array $AssetIds The IDs of the Assets to be copied.
     *
     * @return Model The section model with the Assets associated.
     */
    public function assignAssetFolder($data): Model
    {
        if ($data['type'] === "new") {
            $subfolder = SubFolders::create([
                'name'       => $data['subfolder_name'],
                'slug'       => Str::slug($data['subfolder_name']),
                'section_id' => $data['section_id'],
            ]);
            $subfolderId = $subfolder->id;
            $subfolder->workspaces()->attach($data['workspace_id']);
            $subfolder->sections()->attach($data['section_id']);
        } else {
            $subfolderId = $data['subfolder_id'];
        }
        $subFolderData = $this->subfolderRepository->findById($subfolderId, ['*'], []);

        foreach ($data['asset_id'] as $assetData) {
            $post = Assets::findOrFail($assetData);

                 // Duplicate the attributes
                 if($post){
                    $newPost = $post->replicate();
                    $newPost['asset_key'] = substr(Str::random(20), 0, 14);
                    $newPost['created_by']  = Auth::user()->id;
                     $newPost->save();
                    $newId=$newPost->id;
                    if (! empty($data['workspace_id'])) {
                        $newPost->workspaces()->attach($post->workspaces->pluck('id'));
                    }
                    if (! empty($data['section_id'])) {
                        //$newPost->sections()->attach($post->sections->pluck('id'));
                    }
                    $newId = $newPost->id;
                    $newPost->tags()->attach($post->tags->pluck('id'));
                    $post->metas->each(function ($meta) use ($newPost) {
                        $meta->replicate()->forceFill(['asset_id' => $newPost->id])->save();
                    });
                    $newPost->labels()->attach($post->labels->pluck('id'));
                    $subFolderData->assets()->syncWithoutDetaching([$newId]);
                 }
                 // Duplicate the attributes end
          //  $subFolderData->assets()->syncWithoutDetaching([$assetData]);
        }
        return $subFolderData;
    }

    /**
     * Copy the given Assets to the given section.
     *
     * @param int $sharelink The ID of the section to copy the Assets to.
     * @param array $AssetIds The IDs of the Assets to be copied.
     *
     * @return Model The section model with the Assets associated.
     */
    public function assignAssetShareLink($data)
    {
        foreach ($data['sharelink_id'] as $sharelinkID) {
            $result   = $this->shareLinkRepository->findById($sharelinkID, ['*'], ['assets', 'sections']);
            $now      = Carbon::now();
            $assetIds = $this->shareLinkRepository->getAssetIds($data);
            if (! empty($assetIds)) {
                    // Get already attached asset IDs
                $existingAssetIds = $result->assets()->pluck('assets.id')->toArray();

                // Filter out existing ones
                $newAssetIds = collect($assetIds)->diff($existingAssetIds)->values();

                if ($newAssetIds->isNotEmpty()) {
                    $result->assets()->attach($newAssetIds, [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            // Attach assets (multiple)

            if (! empty($data['section_id']) && is_array($data['section_id'])) {
                $result->sections()->attach($data['section_id']);
            }
        }
    }

    /**
     * Assign the given Assets to the given Collections.
     *
     * @param array $assetIds The IDs of the Assets to be assigned.
     * @param array $collectionIds The IDs of the Collections to be assigned to.
     *
     * @return array An array of assigned Assets with their assigned Collections.
     */
    public function assignAssetsToCollections(array $assetIds, array $collectionIds, array $subfolderIds): array
    {
        $result       = [];
        $assetIdArray = $this->assetRepository->getAssetIds($assetIds, $subfolderIds);
        foreach ($assetIdArray as $assetId) {
            $asset = Assets::find($assetId);

            if ($asset) {
                // Attach collections without detaching previous
                $asset->collections()->syncWithoutDetaching($collectionIds);
                $result[] = [
                    'asset_id'             => $assetId,
                    'assigned_collections' => $collectionIds,
                ];
            }
        }

        /* send notification to user */
        foreach ($collectionIds as $collectionId) {
            $collection = Collections::find($collectionId);
            $notifyUser = $this->notifiyUserAssetAddColection($collection);
        }

        return $result;
    }

    /**
     * Remove all collections from the given assets.
     *
     * @param array $assetIds The IDs of the assets to remove from all collections.
     * @return array An array of results including asset IDs and the count of detached collections.
     */
    public function removeAssetsFromAllCollections(array $assetIds): array
    {
        $result = [];

        foreach ($assetIds as $assetId) {
            $asset = Assets::with(['collections'])->find($assetId);
            // $notifyUser = $this->notifiyUsers($asset->collection);

            if ($asset) {
                $removedCount = $asset->collections()->detach(); // Detach all
                $result[]     = [
                    'asset_id'                  => $assetId,
                    'removed_collections_count' => $removedCount,
                ];
            }
        }

        return $result;
    }

    /**
     * Assign the given Assets to the given Labels.
     *
     * @param array $assetIds The IDs of the Assets to be assigned.
     * @param array $labelIds The IDs of the Labels to be assigned to.
     *
     * @return array An array of assigned Assets with their assigned Labels.
     */
    public function assignAssetsToLabels(array $assetIds, array $labelIds): array
    {
        $result = [];

        foreach ($assetIds as $assetId) {
            $asset = Assets::find($assetId);

            if ($asset) {
                // Attach labels without detaching previous
                $asset->labels()->syncWithoutDetaching($labelIds);
                $result[] = [
                    'asset_id'        => $assetId,
                    'assigned_labels' => $labelIds,
                ];
            }
        }

        return $result;
    }

    /**
     * Remove the given Assets from all Labels.
     *
     * @param array $assetIds The IDs of the Assets to be removed from all Labels.
     *
     * @return array An array of removed Assets with the count of removed Labels.
     */
    public function removeAssetsFromAllLabels(array $assetIds): array
    {
        $result = [];

        foreach ($assetIds as $assetId) {
            $asset = Assets::find($assetId);

            if ($asset) {
                $removedCount = $asset->labels()->detach(); // Detach all
                $result[]     = [
                    'asset_id'             => $assetId,
                    'removed_labels_count' => $removedCount,
                ];
            }
        }
        return $result;
    }

    /**
     * Assign the given Assets to the given Labels.
     *
     * @param array $assetIds The IDs of the Assets to be assigned.
     * @param array $labelIds The IDs of the Labels to be assigned to.
     *
     * @return array An array of assigned Assets with their assigned Labels.
     */

    public function assignAssetsToTags(array $assetsData)
    {
        $result = [];

        foreach ($assetsData as $data) {
            $assetId    = $data['asset_id'] ?? [];
            $addTags    = $data['add_tags'] ?? [];
            $removeTags = $data['remove_tags'] ?? [];
            foreach ($assetId as $assetIds) {
                $asset = Assets::find($assetIds);
                if ($asset) {
                    if (! empty($addTags)) {
                        $asset->tags()->syncWithoutDetaching($addTags);
                    }

                    if (! empty($removeTags)) {
                        $asset->tags()->detach($removeTags);
                    }

                    $result[] = [
                        'asset_id'     => $assetId,
                        'added_tags'   => $addTags,
                        'removed_tags' => $removeTags,
                    ];
                }
            }

        }

        return $result;
    }
    public function getUpdateLog(int $assetId)
    {
        $asset     = Assets::find($assetId);
        $auditLogs = $asset->audits;
        // dd($auditLogs);
        return $auditLogs;
    }

    /**
     * Send notifications to users that are subscribed to the given collection.
     *
     * @param Collection $collection The collection to send notifications for.
     *
     * @return Collection A collection of sent notifications with their associated user.
     */
    public function notifiyUsers($sharelink)
    {
        $user = User::find($sharelink->create_by);
        if (! $user) {
            return null;
        }
        // Send in-app notification if enabled

        if ($sharelink->is_notify) {

            $user->notify(new ShareLinkAppNotification($sharelink));
        }

        // Send mail notification if enabled
        if ($sharelink->is_notify) {
            // $user->notify(new ShareLinkEmailNotification($sharelink));
        }
        $superadmins = User::whereHas('roles', function ($query) {
            $query->where('name', 'Super Admin');
        })->get();
        foreach ($superadmins as $superUser) {
            $superUser->notify(new ShareLinkAppNotification($sharelink));
        }
        // $superUser = User::find($superadmins[0]->id);

        // //  $superUser->notify(new ShareLinkEmailNotification($sharelink));
        // $superUser->notify(new ShareLinkAppNotification($sharelink));
        //dd($superadmins);

        return $sharelink;
    }

    /**
     * Returns an array of asset IDs that correspond to the given section and subfolder IDs.
     *
     * @param array $data An array containing the following keys:
     *                    - `section_id`: An array of section IDs.
     *                    - `subfolder_id`: An array of subfolder IDs.
     *                    - `asset_id`: An array of asset IDs.
     *
     * @return array An array of unique asset IDs.
     */
    public function getAssetIds($data)
    {
        $assetIdsFromRequest    = collect(request('asset_id', []));
        $assetIdsFromSubfolders = [];
        if (! empty($data['subfolder_id']) && is_array($data['subfolder_id'])) {
            // $assetIdsFromSubfolders = SubFolders::with('assets')->whereIn('id', $data['subfolder_id']) ->pluck('asset_id');
            $assetIdsFromSubfolders = \DB::table('subfolder_assets')
                ->whereIn('sub_folder_id', $data['subfolder_id'])
                ->pluck('asset_id')
                ->unique()
                ->values();

        }
        $mergedAssetIds = $assetIdsFromRequest
            ->merge($assetIdsFromSubfolders)
            ->unique()
            ->values();
        return $mergedAssetIds;
    }

    /**
     * Create a new ShareLink.
     * @param array $shareLinkData The data for creating the shareLink.
     * @return Model The created shareLink data.
     */
    public function removeCollectionAsset(int $id, array $collectionData): Model
    {
        $result   = $this->collectionRepository->findById($id);
        $assetIds = $this->shareLinkRepository->getAssetIds($collectionData);
        if (! empty($assetIds)) {
            $result->assets()->detach($assetIds);
        }

        if (! empty($collectionData['section_id']) && is_array($collectionData['section_id'])) {
            $result->sections()->detach($collectionData['section_id']);
        }

        return $result;
    }

    /**
     * Retrieve the common tags from the provided assets data.
     *
     * @param array $assetsData An array of assets from which to retrieve common tags.
     *
     * @return array An array of common tags found in the provided assets data.
     */

    public function getCommonTags($assetIds)
    {
        $commonTagIds = \DB::table('asset_tags')
            ->select('tag_id')
            ->whereIn('asset_id', $assetIds)
            ->groupBy('tag_id')
            ->havingRaw('COUNT(DISTINCT asset_id) = ?', [count($assetIds)])
            ->pluck('tag_id');
        $commonTags = Tags::whereIn('id', $commonTagIds)->get();
        return $commonTags;
    }

    /**
     * Send notifications to users that are subscribed to the given collection.
     *
     * @param Collection $collection The collection to send notifications for.
     *
     * @return Collection A collection of sent notifications with their associated user.
     */
    public function notifyUserForAssetAdd($sharelink, $data)
    {
        $user = User::find($sharelink->create_by);
        if (! $user) {
            return null;
        }
        // Send in-app notification if enabled
        $loginUser = Auth::user();
        $title     = "Asset Added to share link";
        $message   = "{$loginUser->name} added an asset to Sharelink - {$sharelink->name}";
        $type      = "asset_add_to_share_link";
        $url = $sharelink->url;
        //  $url = json_encode($url, JSON_UNESCAPED_SLASHES);
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
     * Send notifications to users that are subscribed to the given collection.
     *
     * @param Collection $collection The collection to send notifications for.
     *
     * @return Collection A collection of sent notifications with their associated user.
     */
    public function notifiyUserAssetAddColection($collection)
    {
        $title         = "Asset Added In collection";
        $loginUser     = Auth::user();
        $message       = "{$loginUser->name} added asset in {$collection->name}";
        $type          = "asset_add_to_collection";
        $url           = env('APP_FE_URL')."collection/{$collection->slug}";
        $notifications = WorkspaceNotification::where('collection_id', $collection->id)->with('user')->get();
        foreach ($notifications as $notification) {
            $user = $notification->user;
            if (! $user) {
                continue;
            }
            // Send in-app notification if enabled
            if ($notification->in_app) {
                $this->collectionRepository->notifyUser($user,
                    $title,
                    $message,
                    $url,
                    $type,
                    $loginUser->name,
                    false,
                    true,
                    $collection->name);
            }
            // Send mail notification if enabled
            if ($notification->is_mail) {
                $this->collectionRepository->notifyUser($user,
                    $title,
                    $message,
                    $url,
                    $type,
                    $loginUser->name,
                    true,
                    false,
                    $collection->name);
            }
        }
        return $notifications;
    }

}
