<?php

namespace App\Repositories;

use App\Models\Collections;
use App\Repositories\BaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Sections;
use App\Models\Assets;
use App\Models\SubFolders;
use Carbon\Carbon;

/**
 * Class CollectionRepository
 *
 * Repository class for interacting with the `COLLECTION` model.
 *
 * @package App\Repositories
 */
class CollectionRepository extends BaseRepository
{
    /**
     * CollectionRepository constructor.
     *
     * @param collection $model The underlying model for the repository.
     */
    public function __construct(Collections $model)
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
    public function collectionWithWorkspace($request): LengthAwarePaginator
    {
        $relation = $request->has('include') ? $request->get('include') : ['users'];
        $perPage = request()->input('per_page', 2);
        $slug = request()->input('slug');
        $append = $request->all();
        $append['per_page'] = $perPage ?? 2;
        $columns = ['*'];
        $searchColumns = ['name', 'slug', 'privacy', 'description'];
        $append['sort_by'] = $append['sort_by'] ?? 'created_at';
        $append['sort_order'] = $append['sort_order'] ?? 'desc';
        $allowedSortFields = ['created_at', 'assets_count'];
        $query = Collections::with(['workspaces' => function ($query) use ($slug) {
            $query->where('slug', $slug);
        }])->withCount(['assets'])->whereHas('workspaces', function ($query) use ($slug) {
            $query->where('slug', $slug);
        });
        $query->when($append['search'], function ($query, $search) use ($searchColumns) {
            $query->where(function ($q) use ($search, $searchColumns) {
                foreach ($searchColumns as $column) {
                    $q->orWhere($column, 'ILIKE', '%' . $search . '%');
                }
            });
        });
        $data = $query->orderBy($append['sort_by'], $append['sort_order'])->paginate($append['per_page']);
        return $data;
    }
    /**
     * Find an collection by their UUID.
     * @param int $collectionUuid The UUID of the collection.
     * @return Model|null The collection model or null if not found.
     */
    public function findByCollectionSlug(string $slug, array $include)
    {
        //dd(Collections::where('slug', $slug)->first());
        return Collections::where('slug', $slug)->first();
    }
    /**
     * Retrieves a collection of sections which contain assets from the given collection
     * and matches the given search filters.
     *
     * @param string $slug The slug of the collection.
     * @param \Illuminate\Http\Request $request The HTTP request object.
     * @return \Illuminate\Support\Collection A collection of sections with filtered counts.
     */
    public function getSelectedCollectionData($slug, $request)
    {
        $search        = trim($request->input('search'));
        $extension     = collect(json_decode($request->input('asset_type')))->map(fn($ext) => strtolower($ext))->toArray();
        $tagIds        = json_decode($request->input('tags'));
        $labelIds      = json_decode($request->input('labels'));
        $collectionIds = json_decode($request->input('collections'));
        $upload_on     = $request->input('upload_on');
        $startDate     = $request->input('start_date');
        $endDate       = $request->input('end_date');
        $recentUpload  = $request->input('recent_upload');
        $perPage       = $request->input('per_page', 5);

        // Get all asset IDs from this collection
        $collection = $this->findByCollectionSlug($slug, ['*'], ['assets']);
        $assetIds   = collect($collection->assets)->pluck('id')->toArray();

        // Extract section:"..." and tag:"..." from search string
        $sectionNames = [];
        $searchTags   = [];

        if (!empty($search)) {
            preg_match_all('/section\s*:\s*"([^"]+)"/i', $search, $sectionMatches);
            if (!empty($sectionMatches[1])) {
                $sectionNames = array_map('trim', $sectionMatches[1]);
            }

            preg_match_all('/tag\s*:\s*"([^"]+)"/i', $search, $tagMatches);
            if (!empty($tagMatches[1])) {
                $searchTags = array_map('trim', $tagMatches[1]);
            }
        }

        /**
         * ----------------------------------------------------
         * 1. Base query: All sections for the collection
         * ----------------------------------------------------
         * No filtering here — ensures all sections appear.
         */
        $sectionsQuery = Sections::where(function ($query) use ($slug) {
            $query->whereHas('assets.collections', fn($q) => $q->where('slug', $slug))
                ->orWhereHas('subfolders.assets.collections', fn($q) => $q->where('slug', $slug));
        });

        /**
         * ----------------------------------------------------
         * 2. Eager load assets with filters applied inside relation
         * ----------------------------------------------------
         */
        $sectionsQuery->with([
            'assets' => function ($query) use (
                $assetIds,
                $sectionNames,
                $searchTags,
                $extension,
                $tagIds,
                $labelIds,
                $collectionIds,
                $upload_on,
                $startDate,
                $endDate,
                $search,
                $recentUpload
            ) {
                $query->where('is_completed', true)
                    ->whereNull('assets.deleted_at')
                    ->whereIn('asset_id', $assetIds);

                // Apply search/section/tag logic inside assets only
                if (!empty($sectionNames)) {
                    $query->whereHas(
                        'sections',
                        fn($sq) =>
                        $sq->whereIn(\DB::raw('LOWER(name)'), array_map('strtolower', $sectionNames))
                    );
                }

                if (!empty($searchTags)) {
                    $query->whereHas('tags', fn($tq) => $tq->where(function ($tagQ) use ($searchTags) {
                        foreach ($searchTags as $tag) {
                            $tagQ->orWhere('name', 'ilike', "%{$tag}%");
                        }
                    }));
                }
                // CASE 3: Section + tag
                if (!empty($sectionNames) && !empty($searchTags)) {
                    $query->where(function ($q) use ($sectionNames, $searchTags) {
                        $q->whereHas('sections', fn($sq) => $sq->whereIn('name', $sectionNames))
                            ->orWhere(function ($taggedQ) use ($sectionNames, $searchTags) {
                                $taggedQ->whereHas('sections', fn($sq) => $sq->whereNotIn('name', $sectionNames))
                                    ->whereHas('tags', fn($tq) => $tq->where(function ($tagQ) use ($searchTags) {
                                        foreach ($searchTags as $tag) {
                                            $tagQ->orWhere('name', 'ilike', "%{$tag}%");
                                        }
                                    }));
                            });
                    });
                }

                if (!empty($search) && empty($sectionNames) && empty($searchTags)) {
                    $this->applyAssetSearchFilter($query, $search);
                }

                // Apply other filters
                $this->applyAssetFilters($query, $extension, $tagIds, $labelIds, $collectionIds, $upload_on, $startDate, $endDate, null, $recentUpload);
            },

            'subfolders.assets' => function ($query) use (
                $assetIds,
                $sectionNames,
                $searchTags,
                $extension,
                $tagIds,
                $labelIds,
                $collectionIds,
                $upload_on,
                $startDate,
                $endDate,
                $search,
                $recentUpload
            ) {
                $query->where('is_completed', true)
                    ->whereNull('assets.deleted_at')
                    ->whereIn('asset_id', $assetIds);

                if (!empty($sectionNames)) {
                    $query->whereHas(
                        'subfolder.sections',
                        fn($sq) =>
                        $sq->whereIn(\DB::raw('LOWER(name)'), array_map('strtolower', $sectionNames))
                    );
                }

                if (!empty($searchTags)) {
                    $query->whereHas('tags', fn($tq) => $tq->where(function ($tagQ) use ($searchTags) {
                        foreach ($searchTags as $tag) {
                            $tagQ->orWhere('name', 'ilike', "%{$tag}%");
                        }
                    }));
                }
                if (!empty($sectionNames) && !empty($searchTags)) {
                    $query->where(function ($q) use ($sectionNames, $searchTags) {
                        $q->whereHas('subfolder.sections', fn($sq) => $sq->whereIn('name', $sectionNames))
                            ->orWhere(function ($taggedQ) use ($sectionNames, $searchTags) {
                                $taggedQ->whereHas('subfolder.sections', fn($sq) => $sq->whereNotIn('name', $sectionNames))
                                    ->whereHas('tags', fn($tq) => $tq->where(function ($tagQ) use ($searchTags) {
                                        foreach ($searchTags as $tag) {
                                            $tagQ->orWhere('name', 'ilike', "%{$tag}%");
                                        }
                                    }));
                            });
                    });
                }

                if (!empty($search) && empty($sectionNames) && empty($searchTags)) {
                    $this->applyAssetSearchFilter($query, $search);
                }

                $this->applyAssetFilters($query, $extension, $tagIds, $labelIds, $collectionIds, $upload_on, $startDate, $endDate, null, $recentUpload);
            }
        ]);

        /**
         * ----------------------------------------------------
         * 3. Get results (no section-level filtering)
         * ----------------------------------------------------
         */
        $sections = $sectionsQuery->orderBy('position', 'asc')->get();

        /**
         * ----------------------------------------------------
         * 4. Post-process for counts
         * ----------------------------------------------------
         */
        $filteredSections = $sections->map(function ($section) {
            $section->assets = $section->assets ?? collect();
            $section->filtered_section_assets_count = $section->assets->count();

            $section->subfolders = $section->subfolders->map(function ($subfolder) {
                $subfolder->assets = $subfolder->assets ?? collect();
                $subfolder->filtered_subfolder_assets_count = $subfolder->assets->count();
                return $subfolder;
            })->values();

            $section->filtered_section_folder_count = $section->subfolders->count();
            return $section;
        });

        return $filteredSections;
    }



    /**
     * Retrieve a collection of collections which have a workspace with a given slug.
     * @param string $slug The slug of the workspace.
     * @param Request $request The request object.
     * @return Collection The collection of collections.
     */
    public function getSelectedCollectionDataBkp($slug, $request)
    {
        $search = trim($request->input('search'));
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
        $assetId = $this->findByCollectionSlug($slug, ['*'], ['assets']);
        $assetIds = [];
        $assetId = $this->findByCollectionSlug($slug, ['*'], ['assets']);
        $assetIds = collect($assetId->assets)->pluck('id')->toArray();

        $sectionSearchOnly = false;
        $folderSearchOnly = false;
        $assetSearchOnly = false;
        $filtersApplied = !empty($extension) || !empty($tagIds) || !empty($labelIds) || !empty($collectionIds) || !empty($upload_on) || (!empty($startDate) && !empty($endDate));
        $sectionNames = null;

        if (!empty($search) && preg_match('/section\s*:\s*(.+)/i', $search, $matches)) {
            $sectionPart = $matches[1];
            $sectionNames = collect(explode(',', $sectionPart))
                ->map(fn($name) => strtolower(trim($name, '" '))) // force lowercase
                ->filter()
                ->values()
                ->toArray();
        }
        if (!empty($search) && empty($sectionNames)) {
            $matchedSections = Sections::whereHas('assets.collections', function ($query) use ($slug) {
                $query->where('slug', $slug);
            })->where('name', 'ilike', "%{$search}%")->with('assets')->pluck('id');

            $matchedFolders = SubFolders::whereHas('assets.collections', function ($query) use ($slug) {
                $query->where('slug', $slug);
            })->where('name', 'ilike', "%{$search}%")->with('assets')->pluck('id');
            // dd($matchedFolders);
            $matchedAssets = Assets::whereHas('collections', function ($query) use ($slug) {
                $query->where('slug', $slug);
            })
                ->where('name', 'ilike', "{$search}%")
                ->orWhere('filename', 'ilike', "{$search}%")
                ->orWhere('description', 'ilike', "{$search}%")
                ->orWhere('extension', 'ilike', "{$search}%")
                ->orWhere('asset_key', 'ilike', "{$search}%")
                ->orWhereHas('tags', fn($tq) => $tq->where('name', 'ilike', "{$search}%"))
                ->orWhereHas('labels', fn($lq) => $lq->where('name', 'ilike', "{$search}%"))
                ->orWhereHas('collections', fn($lq) => $lq->where('name', 'ilike', "{$search}%"))
                ->pluck('id');


            $sectionSearchOnly = $matchedSections->isNotEmpty() && $matchedFolders->isEmpty() && $matchedAssets->isEmpty();
            $folderSearchOnly = $matchedFolders->isNotEmpty() && $matchedSections->isEmpty() && $matchedAssets->isEmpty();
            $assetSearchOnly = $matchedAssets->isNotEmpty() && $matchedSections->isEmpty() && $matchedFolders->isEmpty();
        }

        /* kkkk */
        $sectionsQuery = Sections::where(function ($query) use ($slug) {
            $query->whereHas('assets.collections', function ($q) use ($slug) {
                $q->where('slug', $slug);
            })->orWhereHas('subfolders.assets.collections', function ($q) use ($slug) {
                $q->where('slug', $slug);
            });
        });
        if (!empty($sectionNames)) {
            // If specific section names given
            $sectionsQuery->where(function ($query) use ($sectionNames) {
                foreach ($sectionNames as $name) {
                    $query->orWhereRaw('LOWER(name) = ?', [strtolower($name)]);
                }
            });
            //$sectionsQuery->whereIn('name', $sectionNames);
        } elseif (!empty($search)) {
            // fallback normal search
            $sectionsQuery->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhereHas('subfolders', fn($q2) => $q2->where('name', 'ilike', "{$search}%"))
                    ->orWhereHas('assets', fn($q2) => $this->applyAssetSearchFilter($q2, $search))
                    ->orWhereHas('subfolders.assets', fn($q2) => $this->applyAssetSearchFilter($q2, $search));
            });
        }
        $sectionsQuery->with([
            'assets' => function ($query) use ($assetIds, $sectionSearchOnly, $extension, $tagIds, $labelIds, $collectionIds, $upload_on, $startDate, $endDate, $search, $sectionNames) {

                $query->where('is_completed', true)->whereNull('assets.deleted_at');
                $query->whereIn('asset_id', $assetIds);
                if (!$sectionSearchOnly || !empty($extension) || !empty($tagIds) || !empty($labelIds) || !empty($collectionIds) || !empty($upload_on) || (!empty($startDate) && !empty($endDate))) {
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
            'subfolders.assets' => function ($query) use ($assetIds, $sectionSearchOnly, $folderSearchOnly, $extension, $tagIds, $labelIds, $collectionIds, $upload_on, $startDate, $endDate, $search, $recentUpload, $sectionNames) {
                $query->where('is_completed', true)->whereNull('assets.deleted_at');
                $query->whereIn('asset_id', $assetIds);
                if ($folderSearchOnly) {
                    return;
                }

                if (!$sectionSearchOnly || !empty($extension) || !empty($tagIds) || !empty($labelIds) || !empty($collectionIds) || !empty($upload_on) || (!empty($startDate) && !empty($endDate))) {
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
        ])
            ->orderBy('position', 'asc');
        //->get();
        if (!empty($tagIds)) {
            $sectionsQuery->where(function ($q) use ($tagIds) {
                $q->whereHas('assets.tags', fn($sq) => $sq->whereIn('tags.id', $tagIds))
                    ->orWhereHas('subfolders.assets.tags', fn($sq) => $sq->whereIn('tags.id', $tagIds));
            });
        }

        if (!empty($labelIds)) {
            $sectionsQuery->where(function ($q) use ($labelIds) {
                $q->whereHas('assets.labels', fn($sq) => $sq->whereIn('labels.id', $labelIds))
                    ->orWhereHas('subfolders.assets.labels', fn($sq) => $sq->whereIn('labels.id', $labelIds));
            });
        }

        if (!empty($extension)) {
            $sectionsQuery->where(function ($q) use ($extension) {
                $q->whereHas('assets', fn($sq) => $sq->whereIn(\DB::raw('LOWER(extension)'), $extension))
                    ->orWhereHas('subfolders.assets', fn($sq) => $sq->whereIn(\DB::raw('LOWER(extension)'), $extension));
            });
        }

        if (!empty($upload_on)) {
            $sectionsQuery->where(function ($q) use ($upload_on, $startDate, $endDate) {
                $q->whereHas('assets', fn($sq) => $this->applyUploadOnFilter($sq, $upload_on, $startDate, $endDate))
                    ->orWhereHas('subfolders.assets', fn($sq) => $this->applyUploadOnFilter($sq, $upload_on, $startDate, $endDate));
            });
        }

        $sections = $sectionsQuery->get();


        $filteredSections = $sections->map(function ($section) {
            $section->assets = $section->assets ?? collect();
            $section->filtered_section_assets_count = $section->assets->count();

            $section->subfolders = $section->subfolders->map(function ($subfolder) {
                $subfolder->assets = $subfolder->assets ?? collect();
                $subfolder->filtered_subfolder_assets_count = $subfolder->assets->count();
                return $subfolder;
            })->filter(fn($subfolder) => $subfolder->filtered_subfolder_assets_count > 0)->values();

            $section->filtered_section_folder_count = $section->subfolders->count();
            return $section;
        })->filter(function ($section) {
            return $section->filtered_section_assets_count > 0 || $section->filtered_section_folder_count > 0;
        })->values();

        return $filteredSections;
    }
}
