<?php

namespace App\Services;

use App\Repositories\SubFolderRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class subFolderService
 * Service class for managing CRUD operations of subFolder
 * @package App\Services
 */
class SubFolderService
{
    /**
     * @var SubFolderRepository Repository for interacting with the subFolder data
     */
    protected $subFolderRepository;

    /**
     * subFolderService constructor.
     * @param SubFolderRepository $subFolderRepository The repository for interacting with subFolder data.
     */
    public function __construct(SubFolderRepository $subFolderRepository)
    {
        $this->subFolderRepository = $subFolderRepository;
    }

    /**
     * Create a new SubFolder.
     * @param array $subFolderData The data for creating the subFolder.
     * @return Model The created subFolder data.
     */
    public function createSubFolder(array $subFolderData): Model
    {
        $result = $this->subFolderRepository->create($subFolderData);
        $result->workspaces()->attach($subFolderData['workspace_id']);
        $result->sections()->attach($subFolderData['section_id']);

        return $result;
    }

    /**
     * Get the query builder for subFolder.
     * @return Builder
     */
    public function getSubFolderQuery(): Builder
    {
        return $this->subFolderRepository->query();
    }

    /**
     * Get the query Collection for subFolder.
     * @return LengthAwarePaginator
     */
    public function getSubFolderCollection($request): LengthAwarePaginator
    {
        return $this->subFolderRepository->subfolderWithWorkspace($request);
       // return $this->subFolderRepository->allRelation(['*'], $include);
    }

    /**
     * Find an subFolder by their UUID.
     * @param int $subFolderUuid The UUID of the subFolder.
     * @return Model|null The subFolder model or null if not found.
     */
    public function findBySubFolderId(int $Id, object $request)
    {
        $include = $request->has('include') ? json_decode($request->get('include')) : [];
       return $this->subFolderRepository->subfolderWithFilter($Id,$request);
     
       // return $this->subFolderRepository->findById($Id, ['*'], $include);
    }

    /**
     * Update an existing subFolder.
     * @param int $subFolderId The UUID of the subFolder to be updated.
     * @param array  $subFolderId The data for updating the subFolder.
     * @return model True on successful update, false otherwise.
     */
    public function updateSubFolder(int $subFolderId, array $subFolderData): Model
    {
        $result = $this->subFolderRepository->update($subFolderId, $subFolderData);

        // if (! empty($subFolderData['section_id'])) {
        //     $result->sections()->sync([$subFolderData['section_id']]);
        // }
        if (! empty($subFolderData['workspace_id'])) {
            $result->workspaces()->attach($subFolderData['workspace_id']);
        }
       

        return $result;
    }

    /**
     * Deleting an existing subFolder.
     * @param int $subFolderId The id of the subFolder to be deleted.
     * @return bool True on successful deletion, false otherwise.
     */
    public function deleteSubFolderById(int $subFolderId): bool
    {
        return $this->subFolderRepository->deleteById($subFolderId);
    }
}
