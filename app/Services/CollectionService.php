<?php

namespace App\Services;

use App\Repositories\CollectionRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\WorkspaceNotification;
use App\Notifications\CollectionUpdatedAppNotification;
use App\Notifications\CollectionUpdatedNotification;
use App\Models\Sections;
use App\Models\Collections;
use App\Models\SubFolders;
use App\Models\Assets;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Tags;
use App\Models\Labels;

/**
 * Class collectionService
 * Service class for managing CRUD operations of collection
 * @package App\Services
 */
class CollectionService
{
    /**
     * @var CollectionRepository Repository for interacting with the collection data
     */
    protected $collectionRepository;

    /**
     * collectionService constructor.
     * @param CollectionRepository $collectionRepository The repository for interacting with collection data.
     */
    public function __construct(CollectionRepository $collectionRepository)
    {
        $this->collectionRepository = $collectionRepository;
    }

    /**
     * Create a new Collection.
     * @param array $collectionData The data for creating the collection.
     * @return Model The created collection data.
     */
    public function createCollection(array $collectionData): Model
    {
        // Extract workspace_id if included
        $workspaceId = $collectionData['workspace_id'] ?? null;
        $collectionData['created_by'] = Auth::user()->id;

        // Create the collection
        $collection = $this->collectionRepository->create($collectionData);

        // Attach to workspace if workspace_id is present
        if ($workspaceId) {
            $collection->workspaces()->attach($workspaceId);
        }

        return $collection;
    }

    /**
     * Get the query builder for collection.
     * @return Builder
     */
    public function getCollectionQuery(): Builder
    {
        return $this->collectionRepository->query();
    }

    /**
     * Get the query Collection for collection.
     * @return LengthAwarePaginator
     */
    public function getCollectionsCollection($request): LengthAwarePaginator
    {
        return $this->collectionRepository->collectionWithWorkspace($request);
    }

    /**
     * Find an collection by their UUID.
     * @param int $collectionUuid The UUID of the collection.
     * @return Model|null The collection model or null if not found.
     */
    public function findByCollectionId(int $Id, array $include): ?Model
    {
        return $this->collectionRepository->findById($Id, ['*'], $include);
    }

    /**
     * Find an collection by their UUID.
     * @param int $collectionUuid The UUID of the collection.
     * @return Model|null The collection model or null if not found.
     */
    public function findByCollectionSlug(string $slug, array $include): ?Model
    {
        //dd(Collections::where('slug', $slug)->first());
        return Collections::where('slug', $slug)->first();
    }

    /**
     * Update an existing collection.
     * @param int $collectionId The UUID of the collection to be updated.
     * @param array  $collectionId The data for updating the collection.
     * @return model True on successful update, false otherwise.
     */
    public function updateCollection(int $collectionId, array $collectionData): Model
    {
        return $this->collectionRepository->update($collectionId, $collectionData);
    }

    /**
     * Deleting an existing collection.
     * @param int $collectionId The id of the collection to be deleted.
     * @return bool True on successful deletion, false otherwise.
     */
    public function deleteCollectionById(int $collectionId)
    {
        return $this->collectionRepository->deleteById($collectionId);
    }

    public function notifiyUserCollectionView($collection, $request)
    {
        $title = "Collection Viewed";
        $loginUser = Auth::user();
        $message = "{$loginUser->name} viewed {$collection->name}";
        $type = "collection_viewed";
        $url = env("APP_FE_URL") . "collection/{$collection->slug}";
        $notifications = WorkspaceNotification::where('collection_id', $collection->id)->with('user')->get();

        foreach ($notifications as $notification) {
            $user = $notification->user;

            if (! $user) {
                continue;
            }
            //if owner it self view collection then not send notification
            if ($collection->created_by == $loginUser->id || $loginUser->hasRole('Super Admin')) {
                continue;
            }

            // Send in-app notification if enabled
            if ($notification->in_app && $request->has('is_notification')) {
                $this->collectionRepository->notifyUser(
                    $user,
                    $title,
                    $message,
                    $url,
                    $type,
                    $loginUser->name,
                    false,
                    true,
                    $collection->name
                );
            }
            // Send mail notification if enabled
            if ($notification->is_mail && $request->has('is_notification')) {

                $this->collectionRepository->notifyUser(
                    $user,
                    $title,
                    $message,
                    $url,
                    $type,
                    $loginUser->name,
                    true,
                    false,
                    $collection->name
                );
            }
        }
        return $notifications;
    }


    /**
     * Retrieves the sections and subfolders that contain the selected assets.
     * @param int $Id The id of the share link.
     * @return Collection The filtered sections and subfolders.
     */
    public function getSelectedData(string $slug, object $request)
    {
        return $this->collectionRepository->getSelectedCollectionData($slug, $request);
    }
    public function getTheSuggestion($request)
    {
        $term = trim($request->input('q', ''));
        $slug = $request->input('slug');

        if (!$term) {
            return response()->json([]);
        }

        $suggestions = [];

        // 1️⃣ Get the last part user is typing
        $parts = preg_split('/\s+/', $term);
        $lastPart = end($parts);
        $lastPart = trim($lastPart);

        // Helper to case‑insensitive match
        $likeInsensitive = function ($query, $field, $value) {
            return $query->whereRaw("LOWER($field) LIKE ?", ['%' . strtolower($value) . '%']);
        };

        // 2️⃣ Detect if last part starts with section:, tag:, or label:
        if (stripos($lastPart, 'section:') === 0) {
            $searchValue = trim(str_ireplace('section:', '', $lastPart), '" ');

            $sections = Sections::whereHas('assets.collections', function ($query) use ($slug) {
                $query->where('slug', $slug);
            })
                ->where(function ($q) use ($likeInsensitive, $searchValue) {
                    $likeInsensitive($q, 'name', $searchValue);
                })
                ->limit(5)
                ->pluck('name');

            foreach ($sections as $section) {
                $suggestions[] = preg_replace('/' . preg_quote($lastPart, '/') . '$/i', '', $term) . 'section:"' . $section . '"';
            }
        } elseif (stripos($lastPart, 'tag:') === 0) {
            $searchValue = trim(str_ireplace('tag:', '', $lastPart), '" ');

            $tags = Tags::whereHas('assets.collections', function ($query) use ($slug) {
                $query->where('slug', $slug);
            })
                ->where(function ($q) use ($likeInsensitive, $searchValue) {
                    $likeInsensitive($q, 'name', $searchValue);
                })
                ->limit(5)
                ->pluck('name');

            foreach ($tags as $tag) {
                $suggestions[] = preg_replace('/' . preg_quote($lastPart, '/') . '$/i', '', $term) . 'tag:"' . $tag . '"';
            }
        } elseif (stripos($lastPart, 'label:') === 0) {
            $searchValue = trim(str_ireplace('label:', '', $lastPart), '" ');

            $labels = Labels::whereHas('assets.collections', function ($query) use ($slug) {
                $query->where('slug', $slug);
            })
                ->where(function ($q) use ($likeInsensitive, $searchValue) {
                    $likeInsensitive($q, 'name', $searchValue);
                })
                ->limit(5)
                ->pluck('name');

            // foreach ($labels as $label) {
            //     $suggestions[] = preg_replace('/' . preg_quote($lastPart, '/') . '$/i', '', $term) . 'label:"' . $label . '"';
            // }
        } else {
            // Normal search: suggest all (sections, tags, labels)
            $sections = Sections::whereHas('assets.collections', function ($query) use ($slug) {
                $query->where('slug', $slug);
            })
                ->where(function ($q) use ($likeInsensitive, $lastPart) {
                    $likeInsensitive($q, 'name', $lastPart);
                })
                ->limit(5)
                ->pluck('name');

            $tags = Tags::whereHas('assets.collections', function ($query) use ($slug) {
                $query->where('slug', $slug);
            })
                ->where(function ($q) use ($likeInsensitive, $lastPart) {
                    $likeInsensitive($q, 'name', $lastPart);
                })
                ->limit(5)
                ->pluck('name');

            $labels = Labels::whereHas('assets.collections', function ($query) use ($slug) {
                $query->where('slug', $slug);
            })
                ->where(function ($q) use ($likeInsensitive, $lastPart) {
                    $likeInsensitive($q, 'name', $lastPart);
                })
                ->limit(5)
                ->pluck('name');

            foreach ($sections as $section) {
                $suggestions[] = preg_replace('/' . preg_quote($lastPart, '/') . '$/i', '', $term) . 'section:"' . $section . '"';
            }
            foreach ($tags as $tag) {
                $suggestions[] = preg_replace('/' . preg_quote($lastPart, '/') . '$/i', '', $term) . 'tag:"' . $tag . '"';
            }
            // foreach ($labels as $label) {
            //     $suggestions[] = preg_replace('/' . preg_quote($lastPart, '/') . '$/i', '', $term) . 'label:"' . $label . '"';
            // }
        }

        return $suggestions;
    }
}
