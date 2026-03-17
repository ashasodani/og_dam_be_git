<?php
namespace App\Services;

use App\Repositories\CountryRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class TagService
 * Service class for managing CRUD operations of tag
 * @package App\Services
 */
class PortService
{
    /**
     * @var CountryRepository Repository for interacting with the tag data
     */
    protected $countryRepository;

    /**
     * TagService constructor.
     * @param CountryRepository $countryRepository The repository for interacting with tag data.
     */
    public function __construct(CountryRepository $countryRepository)
    {
        $this->countryRepository = $countryRepository;
    }

    /**
     * Create a new Tag.
     * @param array $tagData The data for creating the tag.
     * @return Model The created tag data.
     */
    public function createCountry($request): Model
    {
        $createdCountry = [];
        

        foreach ($request['country_name'] as $record) {
            $countryData       = ['country_name' => $record,'sheet_name' => $record,'uuid' => '123hsvc7261786187'];
            $country           = $this->countryRepository->create($countryData);
            $createdCountry[] = $country;

            // Attach asset if provided
            // if (! empty($request['asset_id'])) {
            //     $country->assets()->attach($request['asset_id']);
            // }

            // // Attach single or multiple workspace_ids if provided
            // if (! empty($request['workspace_id'])) {
            //     $workspaceIds = is_array($request['workspace_id']) ? $request['workspace_id'] : [$request['workspace_id']];
            //     $tag->workspaces()->attach($workspaceIds);
            // }
        }

        return end($createdCountry);
    }

    /**
     * Get the query builder for tag.
     * @return Builder
     */
    public function getCountryQuery(): Builder
    {
        return $this->countryRepository->query();
    }

    /**
     * Get the query Collection for tag.
     * @return LengthAwarePaginator
     */
    public function getCountryCollection($request): LengthAwarePaginator
    {
        return $this->countryRepository->countryWithWorkspace($request);
    }

    /**
     * Find an tag by their UUID.
     * @param int $tagUuid The UUID of the tag.
     * @return Model|null The tag model or null if not found.
     */
    public function findByCountryId(int $Id, array $include): ?Model
    {
        return $this->countryRepository->findById($Id, ['*'], $include);
    }

    /**
     * Update an existing tag.
     * @param int $tagId The UUID of the tag to be updated.
     * @param array  $tagId The data for updating the tag.
     * @return model True on successful update, false otherwise.
     */

    public function updateCountry(int $countryId, array $countryData): Model
    {
        // Update tag basic fields
        $country = $this->countryRepository->update($countryId, ['name' => $countryData['name'] ?? null]);

        // Sync asset(s) if provided
        // if (! empty($countryData['asset_id'])) {
        //     $assetIds = is_array($countryData['asset_id']) ? $countryData['asset_id'] : [$countryData['asset_id']];
        //     $tag->assets()->sync($assetIds);
        // }

        // Sync workspace(s) if provided
        // if (! empty($tagData['workspace_id'])) {
        //     $workspaceIds = is_array($tagData['workspace_id']) ? $tagData['workspace_id'] : [$tagData['workspace_id']];
        //     $tag->workspaces()->sync($workspaceIds);
        // }

        return $country;
    }

    /**
     * Deleting an existing tag.
     * @param int $countryId The id of the tag to be deleted.
     * @return bool True on successful deletion, false otherwise.
     */
    public function deleteCountryById(int $countryId): bool
    {
        return $this->countryRepository->deleteById($countryId);
    }
}
