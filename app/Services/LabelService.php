<?php
namespace App\Services;

use App\Models\Labels;
use App\Repositories\LabelRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Label;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;


/**
 * Class labelService
 * Service class for managing CRUD operations of label
 * @package App\Services
 */
class LabelService
{
    /**
     * @var LabelRepository Repository for interacting with the label data
     */
    protected $labelRepository;

    /**
     * labelService constructor.
     * @param LabelRepository $labelRepository The repository for interacting with label data.
     */
    public function __construct(LabelRepository $labelRepository)
    {
        $this->labelRepository = $labelRepository;
    }

    /**
     * Create a new Label.
     * @param array $labelData The data for creating the label.
     * @return Model The created label data.
     */
    public function createLabel(array $labelData): Model
    {
        // Extract workspace_id if included
        $workspaceId = $labelData['workspace_id'] ?? null;

        // parent_key can be null or passed in the request (for sub-labels)
        $parentKey               = $labelData['parent_key'] ?? null;
        $labelData['parent_key'] = $parentKey;

        // Create the label
        $label = $this->labelRepository->create($labelData);

        // Attach to workspace if workspace_id is present
        if ($workspaceId) {
            $label->workspaces()->attach($workspaceId);
        }

        return $label;
    }

    /**
     * Get the query builder for label.
     * @return Builder
     */
    public function getLabelQuery(): Builder
    {
        return $this->labelRepository->query();
    }

    /**
     * Get the query Label for label.
     * @return LengthAwarePaginator
     */
    public function getLabelsCollection($request): LengthAwarePaginator
    {
        return $this->labelRepository->labelWithWorkspace($request);
        // $include   = $request->has('include') ? [$request->get('include')] : [];
        // return $this->labelRepository->allRelation(['*'], $include);
    }

    /**
     * Find an label by their UUID.
     * @param int $labelUuid The UUID of the label.
     * @return Model|null The label model or null if not found.
     */
    public function findByLabelId(int $Id, array $include): ?Model
    {
        return $this->labelRepository->findById($Id, ['*'], $include);
    }

    /**
     * Update an existing label.
     * @param int $labelId The UUID of the label to be updated.
     * @param array  $labelId The data for updating the label.
     * @return model True on successful update, false otherwise.
     */

    public function updateLabel(int $labelId, array $labelData): Model
    {
        // Extract workspace_id if included
        $workspaceId    = $labelData['workspace_id'] ?? null;
        $hasWorkspaceId = array_key_exists('workspace_id', $labelData);

        // parent_key can be null or passed in the request
        $parentKey               = $labelData['parent_key'] ?? null;
        $labelData['parent_key'] = $parentKey;

        // Update the label
        $label = $this->labelRepository->update($labelId, $labelData);

        // Update workspace only if it's explicitly provided
        if ($hasWorkspaceId) {
            if ($workspaceId) {
                // Replace the existing workspace(s) with the new one
                $label->workspaces()->sync([$workspaceId]);
            } else {
                // If null or empty, detach all workspaces
                $label->workspaces()->detach();
            }
        }

        return $label;
    }

    /**
     * Deleting an existing label.
     * @param int $labelId The id of the label to be deleted.
     * @return bool True on successful deletion, false otherwise.
     */

    public function deleteLabelById(int $labelId)
    {
        $label = Labels::find($labelId);
        if (! $label) {
            return trans('label.not_found');
        }

        $hasChildren = Labels::where('parent_key', $labelId)->exists();
        if ($hasChildren) {
            return trans('label.child_exists');
        }
        return $this->labelRepository->deleteById($labelId);
    }

     /**
     * Get the query Label for label.
     */
    public function getParentLabelsCollection($request)
    {
        return $this->labelRepository->getParentLabelsCollection($request['slug']);
    }
}
