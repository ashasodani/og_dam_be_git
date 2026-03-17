<?php
namespace App\Services;

use App\Models\Tiles;
use App\Repositories\TilesRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Class TilesService
 * Service class for managing CRUD operations of Tiles
 * @package App\Services
 */
class TilesService
{
    /**
     * @var tilesRepository Repository for interacting with the Tiles data
     */
    protected $tilesRepository;

    /**
     * TilesService constructor.
     * @param tilesRepository $tilesRepository The repository for interacting with tiles data.
     */
    public function __construct(TilesRepository $tilesRepository)
    {
        $this->tilesRepository = $tilesRepository;
    }

    /**
     * Create a new Tiles.
     * @param array $tilesData The data for creating the Tiles.
     * @return Model The created Tiles data.
     */
    public function createTiles($request): Model
    {
        $tilesData = $request->all();

        //s3 upload code start

        $documentfile            = $request->file('tile_image');
        $documentPath            = $this->getFileName($documentfile);
        $documentFiles           = ! empty($documentfile) ? $this->tilesRepository->uploadMaster($documentfile, $documentPath) : '';
        $tilesData['tile_image'] = $request->file('tile_image')->getClientOriginalName();
        $tilesData['tile_url']   = $documentPath;

        //s3 upload code end
        return $this->tilesRepository->create($tilesData);
    }

    /**
     * Get the query builder for Tiles.
     * @return Builder
     */
    public function getTilesQuery(): Builder
    {
        return $this->tilesRepository->query();
    }

    /**
     * Get the query Collection for Tiles.
     * @return Collection
     */
    public function getTilesCollection($request): Collection
    {
        return $this->tilesRepository->tilesWithPortals($request);
        //return $this->tilesRepository->allRelation(['*'], $include);
    }

    /**
     * Find an Tiles by their UUID.
     * @param int $tilesUuid The UUID of the Tiles.
     * @return Model|null The Tiles model or null if not found.
     */
    public function findByTilesId(int $Id, array $include): ?Model
    {
        return $this->tilesRepository->findById($Id, ['*'], $include);
    }

    /**
     * Update an existing Tiles.
     * @param int $tilesId The UUID of the Tiles to be updated.
     * @param array  $tilesId The data for updating the Tiles.
     * @return model True on successful update, false otherwise.
     */
    public function updateTiles(int $tilesId, object $request): Model
    {
        $tilesData = $request->all();

        if ($request->has('tile_image')) {
            $documentfile            = $request->file('tile_image');
            $documentPath            = $this->getFileName($documentfile);
            $documentFiles           = ! empty($documentfile) ? $this->tilesRepository->uploadMaster($documentfile, $documentPath) : '';
            $tilesData['tile_image'] = $request->file('tile_image')->getClientOriginalName();
            $tilesData['tile_url']   = $documentPath;
        }
        return $this->tilesRepository->update($tilesId, $tilesData);
    }
    /**
     * Update an existing position Tiles.
     * @param int $tilesId The UUID of the Tiles to be updated.
     * @param array  $tilesId The data for updating the Tiles.
     * @return model True on successful update, false otherwise.
     */
    public function updatePoisitionTiles(int $tilesId, object $request): Model
    {
        $tilesData = $request->all();
        return $this->tilesRepository->update($tilesId, $tilesData);
    }

    /**
     * Deleting an existing Tiles.
     * @param int $tilesId The id of the Tiles to be deleted.
     * @return bool True on successful deletion, false otherwise.
     */
    public function deleteTilesById(int $tilesId): bool
    {
        return $this->tilesRepository->deleteById($tilesId);
    }

    /**
     * Update the position of multiple Tiles in one go.
     * @param array $tiles An array of associative arrays containing the 'id' and 'position' keys.
     * @return \Illuminate\Support\Collection A collection of updated Tiles models.
     */
    public function bulkUpdateTilePositions(array $tiles): \Illuminate\Support\Collection
    {
        $updatedTiles = collect();

        foreach ($tiles as $tile) {
            $this->tilesRepository->update($tile['id'], ['position' => $tile['position']]);
            $updatedTiles->push(Tiles::find($tile['id']));
        }

        // Sort by position ASC before returning
        return $updatedTiles->sortBy('position')->values();
    }

    public function getFileName($documentfile)
    {
        $documentPath = config('deegest.tile_document.tile_file_path');
        $extension    = $documentfile->getClientOriginalExtension();
        $fileName     = time() . '.' . $extension;
        $documentPath = $documentPath . $fileName;
        return $documentPath;
    }
}
