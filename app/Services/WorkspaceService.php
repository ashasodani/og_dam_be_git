<?php
namespace App\Services;

use App\Models\Workspaces;
use App\Repositories\WorkspaceRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;

/**
 * Class workspaceService
 * Service class for managing CRUD operations of workspace
 * @package App\Services
 */
class WorkspaceService
{
    /**
     * @var WorkspaceRepository Repository for interacting with the workspace data
     */
    protected $workspaceRepository;

    /**
     * workspaceService constructor.
     * @param WorkspaceRepository $workspaceRepository The repository for interacting with workspace data.
     */
    public function __construct(WorkspaceRepository $workspaceRepository)
    {
        $this->workspaceRepository = $workspaceRepository;
    }

    /**
     * Create a new Workspace.
     * @param array $workspaceData The data for creating the workspace.
     * @return Model The created workspace data.
     */
    public function createWorkspace($request): Model
    {

        $workspaceData = $request->all();
        //s3 upload code start
        $originalImage = $request->file('thumbnail');
        $thumbnail = $this->workspaceRepository->imageResize($originalImage,$height=160,$width=320);
        $documentfile  = $thumbnail;
        $docPath = config('deegest.workspace_document.workspace_file_path');
        $documentPath = $this->workspaceRepository->getThumFilename($docPath);
        $documentFiles                    = ! empty($documentfile) ? $this->workspaceRepository->thumbUploadMaster($documentfile, $documentPath) : '';
        $workspaceData['thumbnail_image'] = $request->file('thumbnail')->getClientOriginalName();
        $workspaceData['url']             = $documentPath;
        //s3 upload code end
        $workspace = $this->workspaceRepository->create($workspaceData);

        /* Attach the user to the workspace start */
        $workspace_id = $workspace->id;
        $user         = Auth::user();
        $roleIds      = Auth::user()->roles->pluck('id');
        // $user->user_workspaces()->attach($workspace_id, ['role_id' => $roleIds[0]]);
       
        /* Attach the user to the workspace end */
        return $workspace;
    }

    /**
     * Make file name for workspace
     * @param file $workspaceData The data for creating the workspace.
     *
     */
    public function getFileName($documentfile)
    {
        $documentPath = config('deegest.workspace_document.workspace_file_path');
        $extension    = $documentfile->getClientOriginalExtension();
        $fileName     = time() . '.' . $extension;
        $documentPath = $documentPath . $fileName;
        return $documentPath;
    }

    /**
     * Get the query builder for workspace.
     * @return Builder
     */
    public function getWorkspaceQuery(): Builder
    {
        return $this->workspaceRepository->query();
    }

    /**
     * Get the query Collection for workspace.
     * @return Collection
     */
    public function getWorkspaceCollection($request): Collection
    {
        $include            = $request->has('include') ? [$request->get('include')] : [];
        $perPage            = request()->input('per_page', 10);
        $append             = $request->all();
        $append['per_page'] = $perPage ?? 10;
        return $this->workspaceRepository->allRelation(['*'], $include);
    }

    /**
     * Find an workspace by their UUID.
     * @param int $workspaceUuid The UUID of the workspace.
     * @return Model|null The workspace model or null if not found.
     */
    public function findByWorkspaceId(int $Id, array $include): ?Model
    {
        return $this->workspaceRepository->findById($Id, ['*'], $include);
    }

    /**
     * Find an workspace by their UUID.
     * @param int $workspaceUuid The UUID of the workspace.
     * @return Model|null The workspace model or null if not found.
     */
    public function findByWorkspaceSlug(string $slug): ?Model
    {
        return Workspaces::where('slug', $slug)->first();
    }

    /**
     * Update an existing workspace.
     * @param int $workspaceId The UUID of the workspace to be updated.
     * @param array  $workspaceId The data for updating the workspace.
     * @return model True on successful update, false otherwise.
     */
    public function updateWorkspace(int $workspaceId, object $request): Model
    {
        $workspaceData = $request->all();

        if ($request->has('thumbnail')) {
            $originalImage = $request->file('thumbnail');
            $thumbnail = $this->workspaceRepository->imageResize($originalImage, $height=195,$width=390);
            $documentfile  = $thumbnail;
            $docPath = config('deegest.workspace_document.workspace_file_path');
            $documentPath = $this->workspaceRepository->getThumFilename($docPath);
            $documentFiles                    = ! empty($documentfile) ? $this->workspaceRepository->thumbUploadMaster($documentfile, $documentPath) : '';
            $workspaceData['thumbnail_image'] = $request->file('thumbnail')->getClientOriginalName();
            $workspaceData['url']             = $documentPath;
        }

        return $this->workspaceRepository->update($workspaceId, $workspaceData);
    }

    /**
     * Deleting an existing workspace.
     * @param int $workspaceId The id of the workspace to be deleted.
     * @return bool True on successful deletion, false otherwise.
     */

    public function deleteWorkspaceById(int $workspaceId): array
    {
        try {
            $workspace = $this->workspaceRepository->findById($workspaceId);
        } catch (ModelNotFoundException $e) {
            return ['success' => false, 'reason' => 'workspace_not_found'];
        }

        // Check if it's the last workspace
        $totalWorkspaces = Workspaces::count();

        if ($totalWorkspaces <= 1) {
            return ['success' => false, 'reason' => 'workspace_only_one_left'];
        }

       
        // Check if assets exist
        $workspaceAssets = $workspace->assets()->count();
        if ($workspaceAssets > 0) {
            return ['success' => false, 'reason' => 'workspace_has_assets'];
        }
         // Detach users_workspaces records
        // $workspace->users_workspaces()->delete();
        $workspace->users_workspaces()->detach();

        // Detach invite_users related to this workspace
        // $workspace->users()->delete();
        $workspace->users()->detach();


        // Proceed to delete
        $deleted = $this->workspaceRepository->deleteById($workspaceId);
        return ['success' => $deleted];
    }
}
