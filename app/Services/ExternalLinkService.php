<?php
namespace App\Services;

use App\Models\AddtionalLinks;
use App\Repositories\ExternalLinkRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ExternalLinkService
 * Service class for managing CRUD operations of Tiles
 * @package App\Services
 */
class ExternalLinkService
{
    /**
     * @var externalLinkRepository Repository for interacting with the External Link data
     */
    protected $externalLinkRepository;

    /**
     * TilesService constructor.
     * @param externalLinkRepository $externalLinkRepository The repository for interacting with external link data.
     */
    public function __construct(ExternalLinkRepository $externalLinkRepository)
    {
        $this->externalLinkRepository = $externalLinkRepository;
    }

    /**
     * Create a new External link.
     * @param array $externalLinkData The data for creating the External Link.
     * @return Model The created Tiles data.
     */
    public function createExternalLink(object $request): Model
    {
        $linkData = $request->all();

        //s3 upload code start
         if($request->has('link_icon')){
            $documentfile          = $request->file('link_icon');
            $documentPath          = $this->getFileName($documentfile);
            $documentFiles         = ! empty($documentfile) ? $this->externalLinkRepository->uploadMaster($documentfile, $documentPath) : '';
           // $linkData['link_icon'] = $request->file('link_icon')->getClientOriginalName();
            $linkData['link_icon']  = $documentPath;
         }

        //s3 upload code end
        return $this->externalLinkRepository->create($linkData);
    }

    /**
     * Get the query builder for External Link.
     * @return Builder
     */
    public function getExternalLinkQuery(): Builder
    {
        return $this->externalLinkRepository->query();
    }

    /**
     * Get the query Collection for Tiles.
     * @return Collection
     */
    public function getExternalLinkCollection($request): Collection
    {
        return $this->externalLinkRepository->linksWithPortals($request);
    }

    /**
     * Find an Tiles by their UUID.
     * @param int $tilesUuid The UUID of the Tiles.
     * @return Model|null The Tiles model or null if not found.
     */
    public function findByExternalLinkId(int $Id, array $include): ?Model
    {
        return $this->externalLinkRepository->findById($Id, ['*'], $include);
    }

    /**
     * Update an existing Tiles.
     * @param int $tilesId The UUID of the Tiles to be updated.
     * @param array  $tilesId The data for updating the Tiles.
     * @return model True on successful update, false otherwise.
     */
    public function updateExternalLinks(int $linkId, object $request): Model
    {
        $linkdata = $request->all();
        if($linkdata['is_link_icon'] === 'yes'){
            if ($request->has('link_icon')) {
                 $documentfile          = $request->file('link_icon');
                $documentPath          = $this->getFileName($documentfile);
                $documentFiles         = ! empty($documentfile) ? $this->externalLinkRepository->uploadMaster($documentfile, $documentPath) : '';
              
                $linkdata['link_icon']  = $documentPath;
            }
        } else{
            $linkdata['link_icon'] = null;
        }  
       
        return $this->externalLinkRepository->update($linkId, $linkdata);
    }
    /**
     * Update an existing position Tiles.
     * @param int $tilesId The UUID of the Tiles to be updated.
     * @param array  $tilesId The data for updating the Tiles.
     * @return model True on successful update, false otherwise.
     */
    public function updateExternalLinksPosition(int $tilesId, object $request): Model
    {
        $tilesData = $request->all();
        return $this->externalLinkRepository->update($tilesId, $tilesData);
    }

    /**
     * Deleting an existing Tiles.
     * @param int $tilesId The id of the Tiles to be deleted.
     * @return bool True on successful deletion, false otherwise.
     */
    public function deleteExternalLinkById(int $tilesId): bool
    {
        return $this->externalLinkRepository->deleteById($tilesId);
    }

    /**
     * Update the position of multiple external links in one go.
     *
     * @param array $links An array of associative arrays containing 'id' and 'position' keys for each link.
     * @return \Illuminate\Support\Collection A collection of updated AdditionalLinks models, sorted by position.
     */
    public function bulkUpdateAdditionalLinkPositions($links): \Illuminate\Support\Collection
    {
        $updatedLinks = collect();

        foreach ($links as $link) {
            $this->externalLinkRepository->update($link['id'], ['position' => $link['position']]);
            $updatedLinks->push(AddtionalLinks::find($link['id']));
        }

        // Sort by position ASC before returning
        return $updatedLinks->sortBy('position')->values();
    }

    public function getFileName($documentfile)
    {
        $documentPath = config('deegest.external_link_document.external_link_file_path');
        $extension    = $documentfile->getClientOriginalExtension();
        $fileName     = time() . '.' . $extension;
        $documentPath = $documentPath . $fileName;
        return $documentPath;
    }
}
