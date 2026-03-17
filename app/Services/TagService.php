<?php
namespace App\Services;

use App\Repositories\TagRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class TagService
 * Service class for managing CRUD operations of tag
 * @package App\Services
 */
class TagService
{
    /**
     * @var TagRepository Repository for interacting with the tag data
     */
    protected $tagRepository;

    /**
     * TagService constructor.
     * @param TagRepository $tagRepository The repository for interacting with tag data.
     */
    public function __construct(TagRepository $tagRepository)
    {
        $this->tagRepository = $tagRepository;
    }

    /**
     * Create a new Tag.
     * @param array $tagData The data for creating the tag.
     * @return Model The created tag data.
     */
    public function createTag($request): Model
    {
        $createdTags = [];

        foreach ($request['names'] as $record) {
            $tagData       = ['name' => $record];
            $tag           = $this->tagRepository->create($tagData);
            $createdTags[] = $tag;

            // Attach asset if provided
            if (! empty($request['asset_id'])) {
                $tag->assets()->attach($request['asset_id']);
            }

            // Attach single or multiple workspace_ids if provided
            if (! empty($request['workspace_id'])) {
                $workspaceIds = is_array($request['workspace_id']) ? $request['workspace_id'] : [$request['workspace_id']];
                $tag->workspaces()->attach($workspaceIds);
            }
        }

        return end($createdTags);
    }

    /**
     * Get the query builder for tag.
     * @return Builder
     */
    public function getTagQuery(): Builder
    {
        return $this->tagRepository->query();
    }

    /**
     * Get the query Collection for tag.
     * @return LengthAwarePaginator
     */
    public function getTagCollection($request): LengthAwarePaginator
    {
        return $this->tagRepository->tagWithWorkspace($request);
    }

    /**
     * Find an tag by their UUID.
     * @param int $tagUuid The UUID of the tag.
     * @return Model|null The tag model or null if not found.
     */
    public function findByTagId(int $Id, array $include): ?Model
    {
        return $this->tagRepository->findById($Id, ['*'], $include);
    }

    /**
     * Update an existing tag.
     * @param int $tagId The UUID of the tag to be updated.
     * @param array  $tagId The data for updating the tag.
     * @return model True on successful update, false otherwise.
     */

    public function updateTag(int $tagId, array $tagData): Model
    {
        // Update tag basic fields
        $tag = $this->tagRepository->update($tagId, ['name' => $tagData['name'] ?? null]);

        // Sync asset(s) if provided
        if (! empty($tagData['asset_id'])) {
            $assetIds = is_array($tagData['asset_id']) ? $tagData['asset_id'] : [$tagData['asset_id']];
            $tag->assets()->sync($assetIds);
        }

        // Sync workspace(s) if provided
        if (! empty($tagData['workspace_id'])) {
            $workspaceIds = is_array($tagData['workspace_id']) ? $tagData['workspace_id'] : [$tagData['workspace_id']];
            $tag->workspaces()->sync($workspaceIds);
        }

        return $tag;
    }

    /**
     * Deleting an existing tag.
     * @param int $tagId The id of the tag to be deleted.
     * @return bool True on successful deletion, false otherwise.
     */
    public function deleteTagById(int $tagId): bool
    {
        return $this->tagRepository->deleteById($tagId);
    }
}
