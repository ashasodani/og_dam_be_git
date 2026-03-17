<?php

namespace App\Repositories;

use App\Models\Sections;
use App\Models\Assets;
use App\Models\SubFolders;
use App\Repositories\BaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class SectionRepository
 *
 * Repository class for interacting with the `Sections` model.
 *
 * @package App\Repositories
 */
class SectionRepository extends BaseRepository
{
    /**
     * SectionRepository constructor.
     *
     * @param workspace $model The underlying model for the repository.
     */
    public function __construct(Sections $model)
    {
        $this->model = $model;
    }

    /**
     * Retrieve a paginated collection of collections which have a workspace with a given slug.
     *
     * @param Request $request The request object.
     *
     * @return LengthAwarePaginator
     */
    public function sectionWithWorkspace($request): LengthAwarePaginator
    {
        $relation = $request->has('include') ? $request->get('include') : [];
        // $relation = json_decode($relation);
        //  dd($relation);
        $perPage = request()->input('per_page');
        $slug = request()->input('slug');
        $append = $request->all();
        $append['per_page'] = $perPage ?? 100;
        $columns = ['*'];
        $searchColumns = ['name', 'asset_type'];
        $search = $append['search'] ?? '';

        // $append['sort_by'] = $append['sort_by']??'position';
        // $append['sort_order'] = $append['sort_order']??'desc';

        $query = Sections::whereHas('workspaces', function ($query) use ($slug) {
            $query->where('slug', $slug);
        });
        $query->when($search, function ($query, $search) use ($searchColumns) {
            $query->where(function ($q) use ($search, $searchColumns) {
                foreach ($searchColumns as $column) {
                    $q->orWhere($column, 'LIKE', '%' . $search . '%');
                }
            });
        });

        $data = $query->orderBy('position', 'asc')->paginate($append['per_page']);
        return $data;
    }
    /**
     * Retrieve a paginated collection of collections which have a workspace with a given slug.
     *
     * @param Request $request The request object.
     *
     * @return LengthAwarePaginator
     */
    public function sectionWithAssets($request)
    {
        $search = trim($request->input('search'));
        $slug = $request->input('slug');
        $extension = collect(json_decode($request->input('asset_type')))->map(fn($ext) => strtolower($ext))->toArray();
        $tagIds = json_decode($request->input('tags'));
        $labelIds = json_decode($request->input('labels'));
        $collectionIds = json_decode($request->input('collections'));

        $upload_on = $request->input('upload_on');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $recentUpload = $request->input('recent_upload');
        $perPage = $request->input('per_page', 5);

        $sectionSearchOnly = false;
        $folderSearchOnly = false;
        $assetSearchOnly = false;

        $filtersApplied = !empty($extension) || !empty($tagIds) || !empty($labelIds) || !empty($collectionIds) || !empty($upload_on) || (!empty($startDate) && !empty($endDate));

        $sectionNames = null;

        // 🔍 Detect section:"A","B" syntax
        if (!empty($search) && preg_match('/section\s*:\s*(.+)/i', $search, $matches)) {
            $sectionPart = $matches[1];
            $sectionNames = collect(explode(',', $sectionPart))
                ->map(fn($name) => trim($name, '" '))
                ->filter()
                ->values()
                ->toArray();
        }

        // ⬇️ Find matched Sections/Folders/Assets only if no sectionNames provided
        if (!empty($search) && empty($sectionNames)) {
            $matchedSections = Sections::where('name', 'ilike', "{$search}%")
                ->whereHas('workspaces', fn($q) => $q->where('slug', $slug))
                ->pluck('id');

            $matchedFolders = SubFolders::where('name', 'ilike', "{$search}%")
                ->whereHas('workspaces', fn($q) => $q->where('slug', $slug))
                ->pluck('id');

            $matchedAssets = Assets::whereHas('workspaces', fn($q) => $q->where('slug', $slug))
                ->where(function ($q) use ($search) {
                    $q->where('name', 'ilike', "{$search}%")
                        ->orWhere('filename', 'ilike', "{$search}%")
                        ->orWhere('asset_key', 'ilike', "{$search}%")
                        ->orWhere('description', 'ilike', "{$search}%")
                        ->orWhere('extension', 'ilike', "{$search}%")
                        ->orWhereHas('tags', fn($tq) => $tq->where('name', 'ilike', "{$search}%"))
                        ->orWhereHas('labels', fn($lq) => $lq->where('name', 'ilike', "{$search}%"))
                        ->orWhereHas('collections', fn($cq) => $cq->where('name', 'ilike', "{$search}%"));
                })
                ->pluck('id');

            $sectionSearchOnly = $matchedSections->isNotEmpty() && $matchedFolders->isEmpty() && $matchedAssets->isEmpty();
            $folderSearchOnly = $matchedFolders->isNotEmpty() && $matchedSections->isEmpty() && $matchedAssets->isEmpty();
            $assetSearchOnly = $matchedAssets->isNotEmpty() && $matchedSections->isEmpty() && $matchedFolders->isEmpty();
        }

        // 📋 Build Sections query
        $sectionsQuery = Sections::whereHas('workspaces', fn($q) => $q->where('slug', $slug));

        if (!empty($sectionNames)) {
            // If specific section names given
            $sectionsQuery->whereIn('name', $sectionNames);
        } elseif (!empty($search)) {
            // fallback normal search
            $sectionsQuery->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhereHas('subfolders', fn($q2) => $q2->where('name', 'ilike', "{$search}%"))
                    ->orWhereHas('assets', fn($q2) => $this->applyAssetSearchFilter($q2, $search))
                    ->orWhereHas('subfolders.assets', fn($q2) => $this->applyAssetSearchFilter($q2, $search));
            });
        }

        // 🪄 Eager load relationships with filters
        $sectionsQuery->with([
            'assets' => function ($query) use ($sectionSearchOnly, $extension, $tagIds, $labelIds, $collectionIds, $upload_on, $startDate, $endDate, $search, $sectionNames) {
                $query->where('is_completed', true)->whereNull('assets.deleted_at');

                if (!$sectionSearchOnly) {
                    $this->applyAssetFilters(
                        $query,
                        $extension,
                        $tagIds,
                        $labelIds,
                        $collectionIds,
                        $upload_on,
                        $startDate,
                        $endDate,
                        empty($sectionNames) ? $search : null
                    );
                }
            },

            'subfolders' => function ($query) use ($search, $folderSearchOnly) {
                if ($folderSearchOnly) {
                    $query->where('name', 'ilike', "{$search}%");
                }
            },

            'subfolders.assets' => function ($query) use ($sectionSearchOnly, $folderSearchOnly, $extension, $tagIds, $labelIds, $collectionIds, $upload_on, $startDate, $endDate, $search, $recentUpload, $sectionNames) {
                $query->where('is_completed', true)->whereNull('assets.deleted_at');

                if ($folderSearchOnly) return;

                if (!$sectionSearchOnly) {
                    $this->applyAssetFilters(
                        $query,
                        $extension,
                        $tagIds,
                        $labelIds,
                        $collectionIds,
                        $upload_on,
                        $startDate,
                        $endDate,
                        empty($sectionNames) ? $search : null,
                        $recentUpload
                    );
                }
            }
        ])->orderBy('position', 'asc');

        // ⬇️ Paginate or get
        if ($request->has('per_page')) {
            $sections = $sectionsQuery->paginate($perPage);
        } else {
            $sections = $sectionsQuery->get();
        }

        // 🎨 Post-process results
        $filteredSections = $sections->map(function ($section) use ($sectionSearchOnly, $folderSearchOnly, $assetSearchOnly, $filtersApplied) {
            $section->assets = $section->assets ?? collect();
            $section->filtered_section_assets_count = $section->assets->count();

            $section->subfolders = $section->subfolders->map(function ($subfolder) {
                $subfolder->assets = $subfolder->assets ?? collect();
                $subfolder->filtered_subfolder_assets_count = $subfolder->assets->count();
                $subfolder->no_assets_found = $subfolder->assets->isEmpty();
                return $subfolder;
            })->filter(function ($sf) use ($assetSearchOnly, $filtersApplied, $folderSearchOnly) {
                return $sf->assets->isNotEmpty()
                    || $folderSearchOnly
                    || (!$filtersApplied && !$assetSearchOnly);
            })->values();

            $section->filtered_section_folder_count = $section->subfolders->count();

            return $section;
        })->filter(function ($section) use ($filtersApplied, $assetSearchOnly, $sectionSearchOnly, $folderSearchOnly) {
            return $section->filtered_section_assets_count > 0
                || $section->filtered_section_folder_count > 0
                || $sectionSearchOnly
                || $folderSearchOnly
                || (!$filtersApplied && !$assetSearchOnly);
        })->values();

        return $filteredSections;
    }
    public function sectionWithAssetsId($sectionId, $request)
    {
        $search = trim($request->input('search'));
        $slug = $request->input('slug');
        $extension = $request->input('asset_type');
        $extension = collect(json_decode($extension))->map(fn($ext) => strtolower($ext))->toArray();
        $tagIds = $request->input('tags');
        $tagIds = json_decode($tagIds);
        $labelIds = $request->input('labels');
        $labelIds = json_decode($labelIds);
        $collectionIds = $request->input('collections');
        $collectionIds = json_decode($collectionIds);

        $upload_on = $request->input('upload_on');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $recentUpload = $request->input('recent_upload');
        $perPage = request()->input('per_page', 5);


        $sectionSearchOnly = false;
        $folderSearchOnly = false;
        $assetSearchOnly = false;
        $filtersApplied = !empty($extension) || !empty($tagIds) || !empty($labelIds) || !empty($collectionIds) || !empty($upload_on) || (!empty($startDate) && !empty($endDate));

        if (!empty($search)) {
            $matchedSections = Sections::where('name', 'ilike', "{$search}%")
                ->whereHas('workspaces', fn($q) => $q->where('slug', $slug))
                ->pluck('id');

            $matchedFolders = SubFolders::where('name', 'ilike', "{$search}%")
                ->whereHas('workspaces', fn($q) => $q->where('slug', $slug))
                ->pluck('id');
            // dd($matchedFolders);
            $matchedAssets = Assets::whereHas('workspaces', fn($q) => $q->where('slug', $slug))
                ->where('name', 'ilike', "{$search}%")
                ->orWhere('filename', 'ilike', "{$search}%")
                ->orWhere('asset_key', 'ilike', "{$search}%")
                ->orWhere('description', 'ilike', "{$search}%")
                ->orWhere('extension', 'ilike', "{$search}%")
                ->orWhereHas('tags', fn($tq) => $tq->where('name', 'ilike', "{$search}%"))
                ->orWhereHas('labels', fn($lq) => $lq->where('name', 'ilike', "{$search}%"))
                ->orWhereHas('collections', fn($lq) => $lq->where('name', 'ilike', "{$search}%"))
                ->pluck('id');


            $sectionSearchOnly = $matchedSections->isNotEmpty() && $matchedFolders->isEmpty() && $matchedAssets->isEmpty();
            $folderSearchOnly = $matchedFolders->isNotEmpty() && $matchedSections->isEmpty() && $matchedAssets->isEmpty();
            $assetSearchOnly = $matchedAssets->isNotEmpty() && $matchedSections->isEmpty() && $matchedFolders->isEmpty();
        }

        $sections = Sections::whereHas('workspaces', fn($q) => $q->where('slug', $slug))
            ->when(!empty($search), function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%")
                        ->orWhereHas('subfolders', fn($q2) => $q2->where('name', 'ilike', "{$search}%"))
                        ->orWhereHas('assets', fn($q2) => $this->applyAssetSearchFilter($q2, $search))
                        ->orWhereHas('subfolders.assets', fn($q2) => $this->applyAssetSearchFilter($q2, $search));
                });
            })
            ->with([
                'assets' => function ($query) use ($sectionSearchOnly, $extension, $tagIds, $labelIds, $collectionIds, $upload_on, $startDate, $endDate, $search) {
                    $query->where('is_completed', true)->whereNull('assets.deleted_at');

                    if (!$sectionSearchOnly) {
                        $this->applyAssetFilters($query, $extension, $tagIds, $labelIds, $collectionIds, $upload_on, $startDate, $endDate, $search);
                    }
                },
                'subfolders' => function ($query) use ($search, $folderSearchOnly) {
                    if ($folderSearchOnly) {
                        $query->where('name', 'ilike', "{$search}%");
                    }
                },
                'subfolders.assets' => function ($query) use ($sectionSearchOnly, $folderSearchOnly, $extension, $tagIds, $labelIds, $collectionIds, $upload_on, $startDate, $endDate, $search, $recentUpload) {
                    $query->where('is_completed', true)->whereNull('assets.deleted_at');

                    if ($folderSearchOnly) {
                        return;
                    }

                    if (!$sectionSearchOnly) {
                        $this->applyAssetFilters($query, $extension, $tagIds, $labelIds, $collectionIds, $upload_on, $startDate, $endDate, $search, $recentUpload);
                    }
                }
            ])
            ->where('id', $sectionId)->orderBy('position', 'asc')->get();


        $filteredSections = $sections->map(function ($section) use ($sectionSearchOnly, $folderSearchOnly, $assetSearchOnly, $filtersApplied) {
            $section->assets = $section->assets ?? collect();
            $section->filtered_section_assets_count = $section->assets->count();

            $section->subfolders = $section->subfolders->map(function ($subfolder) {
                $subfolder->assets = $subfolder->assets ?? collect();
                $subfolder->filtered_subfolder_assets_count = $subfolder->assets->count();
                $subfolder->no_assets_found = $subfolder->assets->isEmpty();
                return $subfolder;
            })->filter(function ($sf) use ($assetSearchOnly, $filtersApplied, $folderSearchOnly) {
                return $sf->assets->isNotEmpty()
                    || $folderSearchOnly
                    || (!$filtersApplied && !$assetSearchOnly);
            })->values();

            $section->filtered_section_folder_count = $section->subfolders->count();

            return $section;
        })->filter(function ($section) use ($filtersApplied, $assetSearchOnly, $sectionSearchOnly, $folderSearchOnly) {
            return $section->filtered_section_assets_count > 0
                || $section->filtered_section_folder_count > 0
                || $sectionSearchOnly
                || $folderSearchOnly
                || (!$filtersApplied && !$assetSearchOnly);
        })->values();

        return $filteredSections;
    }
}
