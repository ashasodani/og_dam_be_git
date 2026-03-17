<?php

namespace App\Services;

use App\Models\Sections;
use App\Repositories\SectionRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Tags;
use App\Models\Labels;

/**
 * Class sectionService
 * Service class for managing CRUD operations of section
 * @package App\Services
 */
class SectionService
{
    /**
     * @var SectionRepository Repository for interacting with the section data
     */
    protected $sectionRepository;

    /**
     * sectionService constructor.
     * @param SectionRepository $sectionRepository The repository for interacting with section data.
     */
    public function __construct(SectionRepository $sectionRepository)
    {
        $this->sectionRepository = $sectionRepository;
    }

    /**
     * Create a new section.
     * @param array $sectionData The data for creating the section.
     * @return Model The created section data.
     */
    public function createSection(array $sectionData): Model
    {
        $sectionData['asset_type']         = $sectionData['default_asset_type'];
        $sectionData['default_asset_type'] = 'files';
        $result                            = $this->sectionRepository->create($sectionData);
        //dd($sectionData);
        $result->workspaces()->attach($sectionData['workspace_id']);

        if (! empty($sectionData['collection_id'])) {
            $result->collections()->attach($sectionData['collection_id']);
        }

        return $result;
    }

    /**
     * Get the query builder for section.
     * @return Builder
     */
    public function getSectionQuery(): Builder
    {
        return $this->sectionRepository->query();
    }

    /**
     * Get the query Collection for section.
     * @return LengthAwarePaginator
     */
    public function getSectionCollection($request): LengthAwarePaginator
    {
        // dd($include);
        return $this->sectionRepository->sectionWithWorkspace($request);
        // return $this->sectionRepository->allRelation(['*'], $include);
    }
    /**
     * Get the query Collection for section.
     * @return LengthAwarePaginator
     */
    public function getSectionAssets($request)
    {
        return $this->sectionRepository->sectionWithAssets($request);
    }
    /**
     * Retrieve a paginated collection of assets which have a section with a given ID.
     *
     * @param int $sectionId The ID of the section.
     * @param Request $request The request object.
     *
     * @return LengthAwarePaginator
     */
    public function getSectionAssetsId($sectionId, $request)
    {
        return $this->sectionRepository->sectionWithAssetsId($sectionId, $request);
    }

    /**
     * Get the query Collection for section.
     * @return Collection
     */
    public function getAllSectionCollection(): Collection
    {
        return $this->sectionRepository->allRelation(['*']);
    }

    /**
     * Find an section by their ID.
     * @param int $sectionID The ID of the workspace.
     * @return Model|null The workspace model or null if not found.
     */
    public function findByWorkspaceId(int $Id, object $request): ?Model
    {
        $include = $request->get('include') ? $request->get('include') : [];
        return $this->sectionRepository->findById($Id, ['*'], $include);
    }

    /**
     * Update an existing section.
     * @param int $sectionId The ID of the section to be updated.
     * @param array  $sectionData The data for updating the workspace.
     * @return model True on successful update, false otherwise.
     */

    public function updateSection(int $sectionId, array $sectionData): Model
    {
        $sectionData['asset_type']         = $sectionData['default_asset_type'];
        $sectionData['default_asset_type'] = 'files';
        $section                           = $this->sectionRepository->update($sectionId, $sectionData);

        if (! empty($sectionData['workspace_id'])) {
            $section->workspaces()->sync([$sectionData['workspace_id']]);
        }

        if (! empty($sectionData['collection_id'])) {
            $section->collections()->sync([$sectionData['collection_id']]);
        }

        return $section;
    }

    /**
     * Deleting an existing section.
     * @param int $sectionId The id of the workspace to be deleted.
     * @return bool True on successful deletion, false otherwise.
     */
    public function deleteSectionById(int $sectionId): bool
    {

        return $this->sectionRepository->deleteById($sectionId);
    }

    /**
     * Update the position of a section.
     *
     * @param int $sectionId The id of the section to be updated.
     * @param object $request The request containing the validated data for updating section.
     *
     * @return Model The updated section model.
     */
    public function updatePoisitionSections(int $sectionId, object $request): Model
    {
        $sectionData = $request->all();
        return $this->sectionRepository->update($sectionId, $sectionData);
    }

    /**
     * Update the position of multiple sections in one go.
     *
     * @param array $sections An array of associative arrays, each containing 'id' and 'position' for a section.
     * @return \Illuminate\Support\Collection A collection of updated Sections models, sorted by position.
     */
    public function bulkUpdateSectionsPosition(array $sections): \Illuminate\Support\Collection
    {
        $updatedSections = collect();

        foreach ($sections as $section) {
            $this->sectionRepository->update($section['id'], ['position' => $section['position']]);
            $updatedSections->push(Sections::find($section['id']));
        }

        // Sort by position ASC before returning
        return $updatedSections->sortBy('position')->values();
    }
    /**
     * Generate search suggestions for sections, tags, and labels based on the input query.
     *
     * This function analyzes the user's input query to determine the type of suggestion
     * required (section, tag, or label). It then searches the database for matching entries
     * and returns a list of suggestions formatted as part of the original query.
     *
     * @param Request $request The request object containing the input query and workspace slug.
     * @return array An array of formatted suggestion strings.
     */

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

            $sections = Sections::whereHas('workspaces', fn($q) => $q->where('slug', $slug))
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

            $tags = Tags::whereHas('workspaces', fn($q) => $q->where('slug', $slug))
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

            $labels = Labels::whereHas('workspaces', fn($q) => $q->where('slug', $slug))
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
            $sections = Sections::whereHas('workspaces', fn($q) => $q->where('slug', $slug))
                ->where(function ($q) use ($likeInsensitive, $lastPart) {
                    $likeInsensitive($q, 'name', $lastPart);
                })
                ->limit(5)
                ->pluck('name');

            $tags = Tags::whereHas('workspaces', fn($q) => $q->where('slug', $slug))
                ->where(function ($q) use ($likeInsensitive, $lastPart) {
                    $likeInsensitive($q, 'name', $lastPart);
                })
                ->limit(5)
                ->pluck('name');

            $labels = Labels::whereHas('workspaces', fn($q) => $q->where('slug', $slug))
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
