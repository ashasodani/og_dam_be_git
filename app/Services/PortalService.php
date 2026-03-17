<?php
namespace App\Services;

use App\Repositories\PortalRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Models\Portals;

/**
 * Class PortalService
 * Service class for managing CRUD operations of Portal
 * @package App\Services
 */
class PortalService
{
    /**
     * @var PortalRepository Repository for interacting with the Portal data
     */
    protected $portalRepository;

    /**
     * PortalService constructor.
     * @param portalRepository $portalRepository The repository for interacting with Portal data.
     */
    public function __construct(portalRepository $portalRepository)
    {
        $this->portalRepository = $portalRepository;
    }

    /**
     * Create a new Portal.
     * @param array $PortalData The data for creating the Portal.
     * @return Model The created Portal data.
     */
    public function createPortal($request): Model
    {
        $PortalData = $request->all();

        //s3 upload code start

        $documentfile                  = $request->file('thumbnail');
        $documentPath                  = $this->getFileName($documentfile);
        $documentFiles                 = ! empty($documentfile) ? $this->portalRepository->uploadMaster($documentfile, $documentPath) : '';
        $PortalData['thumbnail_image'] = $request->file('thumbnail')->getClientOriginalName();
        $PortalData['url']             = $documentPath;

        $headerDocumentFile         = $request->file('header_image');
        $headerDocumentPath         = $this->getFileName($headerDocumentFile);
        $headerDocumentFiles        = ! empty($headerDocumentFile) ? $this->portalRepository->uploadMaster($headerDocumentFile, $headerDocumentPath) : '';
        $PortalData['header_image'] = $request->file('header_image')->getClientOriginalName();
        $PortalData['header_url']             = $headerDocumentPath;
        $portal = $this->portalRepository->create($PortalData);

        /* Attach the user to the portal start */
        $portal_id = $portal->id;
        $user      = Auth::user();
        $roleIds   = Auth::user()->roles->pluck('id');
        $user->user_portals()->attach($portal_id, ['role_id' => $roleIds[0]]);
        /* Attach the user to the portal end */
        return $portal;
    }

    /**
     * Get the query builder for Portal.
     * @return Builder
     */
    public function getPortalQuery(): Builder
    {
        return $this->portalRepository->query();
    }

    /**
     * Get the query Collection for Portal.
     * @return Collection
     */
    public function getPortalCollection($include): Collection
    {
        return $this->portalRepository->allRelation(['*'], $include);
    }

    /**
     * Find an Portal by their UUID.
     * @param int $PortalUuid The UUID of the Portal.
     * @return Model|null The Portal model or null if not found.
     */
    public function findByPortalId(int $Id, array $include): ?Model
    {
        return $this->portalRepository->findById($Id, ['*'], $include);
    }
     /**
     * Find an workspace by their UUID.
     * @param int $workspaceUuid The UUID of the workspace.
     * @return Model|null The workspace model or null if not found.
     */
    public function findByPortalSlug(string $slug): ?Model
    {
        return Portals::where('slug', $slug)->first();
    }

    /**
     * Update an existing Portal.
     * @param int $portalId The UUID of the Portal to be updated.
     * @param array  $portalId The data for updating the Portal.
     * @return model True on successful update, false otherwise.
     */
    public function updatePortal(int $portalId, object $request): Model
    {
        $PortalData = $request->all();
        if ($request->has('thumbnail')) {
            $documentFile                  = $request->file('thumbnail');
            $documentPath                  = $this->getFileName($documentFile);
            $documentFiles                 = ! empty($documentFile) ? $this->portalRepository->uploadMaster($documentFile, $documentPath) : '';
            $PortalData['thumbnail_image'] = $request->file('thumbnail')->getClientOriginalName();
            $PortalData['url']             = $documentPath;
        }

        if ($request->has('header_image')) {
            $headerDocumentFile         = $request->file('header_image');
            $headerDocumentPath         = $this->getFileName($headerDocumentFile);
            $headerDocumentFiles        = ! empty($headerDocumentFile) ? $this->portalRepository->uploadMaster($headerDocumentFile, $headerDocumentPath) : '';
            $PortalData['header_image'] = $request->file('header_image')->getClientOriginalName();
            $PortalData['header_url']   = $headerDocumentPath;
        }

        return $this->portalRepository->update($portalId, $PortalData);
    }

    /**
     * Deleting an existing Portal.
     * @param int $portalId The id of the Portal to be deleted.
     * @return bool True on successful deletion, false otherwise.
     */
    public function deletePortalById(int $portalId): bool
    {
        return $this->portalRepository->deleteById($portalId);
    }

    /**
     * Create a newrelated portal
     * @param array $externalLinkData The data for creating the External Link.
     * @return Model The created Tiles data.
     */
    public function createRelatedPortal(object $request): Model
    {
        $portal = $this->portalRepository->findById($request->portal_id, ['*']);
        // $portal->relatedPortal()->detach();
        $portal->relatedPortal()->sync($request->related_portal_id);
        return $portal;
    }

    /**
     * Get the query Collection for Portal.
     * @return Collection
     */
    public function getRelatedPortalCollection($request): Collection
    {
        //return $this->portalRepository->allRelation(['*'], $include);
        return $this->portalRepository->relatedWithPortals($request);
    }

    /**
     * Generate a file path for the given document file.
     * 
     * @param mixed $documentfile The document file whose path is to be generated.
     * @return string The complete file path including the file name and extension.
     */

    public function getFileName($documentfile)
    {
        $documentPath = config('deegest.portal_document.portal_file_path');
        $extension    = $documentfile->getClientOriginalExtension();
        $fileName     = time() . '.' . $extension;
        $documentPath = $documentPath . $fileName;
        return $documentPath;
    }
}
