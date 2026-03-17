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

        $filtersApplied = !empty($extension) || !empty($tagIds) || !empty($labelIds) || !empty($collectionIds) || !empty($upload_on) || (!empty($startDate) && !empty($endDate));

        /**
         * ----------------------------------------------------
         * 1. Parse section:"..." and tag:"..."
         * ----------------------------------------------------
         */
        $sectionNames = [];
        $searchTags = [];

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
         * Helper for LIKE tag query
         * ----------------------------------------------------
         */
        $tagLikeQuery = function ($query, $tags) {
            $query->where(function ($q) use ($tags) {
                foreach ($tags as $tag) {
                    $q->orWhere('name', 'ilike', '%' . $tag . '%'); // PostgreSQL
                }
            });
        };

        /**
         * ----------------------------------------------------
         * 2. Build Sections query
         * ----------------------------------------------------
         */
        $sectionsQuery = Sections::whereHas('workspaces', fn($q) => $q->where('slug', $slug));

        // Case 1: Only section
        if (!empty($sectionNames) && empty($searchTags)) {
            $sectionsQuery->whereIn('name', $sectionNames);
        }
        // Case 2: Only tag
        elseif (empty($sectionNames) && !empty($searchTags)) {
            $sectionsQuery->where(function ($q) use ($searchTags, $tagLikeQuery) {
                $q->whereHas('assets.tags', fn($tq) => $tagLikeQuery($tq, $searchTags))
                    ->orWhereHas('subfolders.assets.tags', fn($tq) => $tagLikeQuery($tq, $searchTags));
            });
        }
        // Case 3: Section + tag
        elseif (!empty($sectionNames) && !empty($searchTags)) {
            $sectionsQuery->where(function ($q) use ($sectionNames, $searchTags, $tagLikeQuery) {
                $q->whereIn('name', $sectionNames)
                    ->orWhere(function ($qq) use ($sectionNames, $searchTags, $tagLikeQuery) {
                        $qq->whereNotIn('name', $sectionNames)
                            ->where(function ($tagQ) use ($searchTags, $tagLikeQuery) {
                                $tagQ->whereHas('assets.tags', fn($tq) => $tagLikeQuery($tq, $searchTags))
                                    ->orWhereHas('subfolders.assets.tags', fn($tq) => $tagLikeQuery($tq, $searchTags));
                            });
                    });
            });
        }
        // Fallback: normal keyword search
        elseif (!empty($search)) {
            $sectionsQuery->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhereHas('subfolders', fn($q2) => $q2->where('name', 'ilike', "{$search}%"))
                    ->orWhereHas('assets', fn($q2) => $this->applyAssetSearchFilter($q2, $search))
                    ->orWhereHas('subfolders.assets', fn($q2) => $this->applyAssetSearchFilter($q2, $search));
            });
        }

        /**
         * ----------------------------------------------------
         * 3. Eager load assets & subfolders
         * ----------------------------------------------------
         */
        $sectionsQuery->with([
            'assets' => function ($query) use ($sectionNames, $searchTags, $extension, $tagIds, $labelIds, $collectionIds, $upload_on, $startDate, $endDate, $search, $tagLikeQuery) {
                $query->where('is_completed', true)->whereNull('assets.deleted_at');

                // Only tag filter
                if (empty($sectionNames) && !empty($searchTags)) {
                    $query->whereHas('tags', fn($tq) => $tagLikeQuery($tq, $searchTags));
                }

                // Section + tag filter
                if (!empty($sectionNames) && !empty($searchTags)) {
                    $query->where(function ($q) use ($sectionNames, $searchTags, $tagLikeQuery) {
                        $q->whereHas('sections', fn($sq) => $sq->whereIn('name', $sectionNames))
                            ->orWhere(function ($taggedQ) use ($sectionNames, $searchTags, $tagLikeQuery) {
                                $taggedQ->whereHas('sections', fn($sq) => $sq->whereNotIn('name', $sectionNames))
                                    ->whereHas('tags', fn($tq) => $tagLikeQuery($tq, $searchTags))
                                    ->where('is_completed', true)
                                    ->whereNull('assets.deleted_at');
                            });
                    });
                }

                // Always apply asset filters (including section-only case)
                $this->applyAssetFilters(
                    $query,
                    $extension,
                    $tagIds,
                    $labelIds,
                    $collectionIds,
                    $upload_on,
                    $startDate,
                    $endDate,
                    (empty($sectionNames) && empty($searchTags)) ? $search : null
                );
            },

            'subfolders.assets' => function ($query) use ($sectionNames, $searchTags, $extension, $tagIds, $labelIds, $collectionIds, $upload_on, $startDate, $endDate, $search, $recentUpload, $tagLikeQuery) {
                $query->where('is_completed', true)->whereNull('assets.deleted_at');

                // Only tag filter
                if (empty($sectionNames) && !empty($searchTags)) {
                    $query->whereHas('tags', fn($tq) => $tagLikeQuery($tq, $searchTags));
                }

                // Section + tag filter
                if (!empty($sectionNames) && !empty($searchTags)) {
                    $query->where(function ($q) use ($sectionNames, $searchTags, $tagLikeQuery) {
                        $q->whereHas('subfolder.sections', fn($sq) => $sq->whereIn('name', $sectionNames))
                            ->orWhere(function ($taggedQ) use ($sectionNames, $searchTags, $tagLikeQuery) {
                                $taggedQ->whereHas('subfolder.sections', fn($sq) => $sq->whereNotIn('name', $sectionNames))
                                    ->whereHas('tags', fn($tq) => $tagLikeQuery($tq, $searchTags))
                                    ->where('is_completed', true)
                                    ->whereNull('assets.deleted_at');
                            });
                    });
                }

                // Always apply asset filters (including section-only case)
                $this->applyAssetFilters(
                    $query,
                    $extension,
                    $tagIds,
                    $labelIds,
                    $collectionIds,
                    $upload_on,
                    $startDate,
                    $endDate,
                    (empty($sectionNames) && empty($searchTags)) ? $search : null,
                    $recentUpload
                );
            }
        ]);

        /**
         * ----------------------------------------------------
         * 4. Apply section-level filters
         * ----------------------------------------------------
         */
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
        if (!empty($collectionIds)) {
            $sectionsQuery->whereHas('assets.collections', function ($q) use ($collectionIds) {
                $q->whereIn('collections.id', $collectionIds);
            })->orWhereHas('subfolders.assets.collections', function ($q) use ($collectionIds) {
                $q->whereIn('collections.id', $collectionIds);
            });
        }

        if (!empty($upload_on)) {
            $sectionsQuery->where(function ($q) use ($upload_on, $startDate, $endDate) {
                $q->whereHas('assets', fn($sq) => $this->applyUploadOnFilter($sq, $upload_on, $startDate, $endDate))
                    ->orWhereHas('subfolders.assets', fn($sq) => $this->applyUploadOnFilter($sq, $upload_on, $startDate, $endDate));
            });
        }

        /**
         * ----------------------------------------------------
         * 5. Final results
         * ----------------------------------------------------
         */
        $sections = $sectionsQuery->orderBy('position', 'asc')->paginate($perPage);

        /**
         * ----------------------------------------------------
         * 6. Post-process results
         * ----------------------------------------------------
         */
        $filteredSections = $sections->map(function ($section) use ($filtersApplied) {
            $section->assets = $section->assets ?? collect();
            $section->filtered_section_assets_count = $section->assets->count();

            $section->subfolders = $section->subfolders->map(function ($subfolder) {
                $subfolder->assets = $subfolder->assets ?? collect();
                $subfolder->filtered_subfolder_assets_count = $subfolder->assets->count();
                $subfolder->no_assets_found = $subfolder->assets->isEmpty();
                return $subfolder;
            })->values();

            $section->filtered_section_folder_count = $section->subfolders->count();
            return $section;
        });

        return $filteredSections;
    }
    public function sectionWithAssetsBkp($request)
    {
        $search = trim($request->input('search'));
        $slug = $request->input('slug');
        $extension = collect(json_decode($request->input('asset_type')))->map(fn($ext) => strtolower($ext))->toArray();
        $tagIds = json_decode($request->input('tags'));

        $labelIds = json_decode($request->input('labels'));
        $collectionIds = json_decode($request->input('collections'));
         dd($collectionIds);
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
        ]);
        /* filter apply start */
        if (!empty($tagIds)) {
            $sectionsQuery->whereHas('assets.tags', function ($q) use ($tagIds) {
                $q->whereIn('tags.id', $tagIds);
            })->orWhereHas('subfolders.assets.tags', function ($q) use ($tagIds) {
                $q->whereIn('tags.id', $tagIds);
            });
        }
        if (!empty($collectionIds)) {
            $sectionsQuery->whereHas('assets.collections', function ($q) use ($collectionIds) {
                $q->whereIn('collections.id', $collectionIds);
            })->orWhereHas('subfolders.assets.collections', function ($q) use ($collectionIds) {
                $q->whereIn('collections.id', $collectionIds);
            });
        }
        if (!empty($labelIds)) {
            $sectionsQuery->whereHas('assets.labels', function ($q) use ($labelIds) {
                $q->whereIn('labels.id', $labelIds);
            })->orWhereHas('subfolders.assets.labels', function ($q) use ($labelIds) {
                $q->whereIn('labels.id', $labelIds);
            });
        }
        if (!empty($extension)) {
            $sectionsQuery->where(function ($q) use ($extension) {
                $q->whereHas('assets', function ($subQ) use ($extension) {
                    $subQ->whereIn(\DB::raw('LOWER(extension)'), $extension);
                })->orWhereHas('subfolders.assets', function ($subQ) use ($extension) {
                    $subQ->whereIn(\DB::raw('LOWER(extension)'), $extension);
                });
            });
        }
        if (!empty($upload_on)) {
            $sectionsQuery->where(function ($q) use ($upload_on, $startDate, $endDate) {
                $q->whereHas('assets', function ($subQ) use ($upload_on, $startDate, $endDate) {
                    $this->applyUploadOnFilter($subQ, $upload_on, $startDate, $endDate);
                })->orWhereHas('subfolders.assets', function ($subQ) use ($upload_on, $startDate, $endDate) {
                    $this->applyUploadOnFilter($subQ, $upload_on, $startDate, $endDate);
                });
            });
        }

        /* filter apply end */
        $sectionsQuery->orderBy('position', 'asc');
        // if ($filtersApplied || !empty($search)) {
        //     $request->merge(['page' => 1]);
        // }
        // ⬇️ Paginate or get
        //if ($request->has('per_page')) {
        $sections = $sectionsQuery->paginate($perPage);
        // } else {
        //     $sections = $sectionsQuery->get();
        // }

        /**
         * ----------------------------------------------------
         * 6. Post-process results
         * ----------------------------------------------------
         */
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
        $extension = collect(json_decode($request->input('asset_type')))->map(fn($ext) => strtolower($ext))->toArray();
        $tagIds = json_decode($request->input('tags'));
        $labelIds = json_decode($request->input('labels'));
        $collectionIds = json_decode($request->input('collections'));
        $upload_on = $request->input('upload_on');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $recentUpload = $request->input('recent_upload');
        $perPage = $request->input('per_page', 5);

        $filtersApplied = !empty($extension) || !empty($tagIds) || !empty($labelIds) || !empty($collectionIds) || !empty($upload_on) || (!empty($startDate) && !empty($endDate));

        /**
         * ----------------------------------------------------
         * 1. Parse section:"..." and tag:"..."
         * ----------------------------------------------------
         */
        $sectionNames = [];
        $searchTags = [];

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
         * Helper for LIKE tag query
         * ----------------------------------------------------
         */
        $tagLikeQuery = function ($query, $tags) {
            $query->where(function ($q) use ($tags) {
                foreach ($tags as $tag) {
                    $q->orWhere('name', 'ilike', '%' . $tag . '%'); // PostgreSQL
                }
            });
        };

        /**
         * ----------------------------------------------------
         * 2. Build Sections query
         * ----------------------------------------------------
         */
        $sectionsQuery = Sections::whereHas('workspaces', fn($q) => $q->where('slug', $slug));

        // Case 1: Only section
        if (!empty($sectionNames) && empty($searchTags)) {
            $sectionsQuery->whereIn('name', $sectionNames);
        }
        // Case 2: Only tag
        elseif (empty($sectionNames) && !empty($searchTags)) {
            $sectionsQuery->where(function ($q) use ($searchTags, $tagLikeQuery) {
                $q->whereHas('assets.tags', fn($tq) => $tagLikeQuery($tq, $searchTags))
                    ->orWhereHas('subfolders.assets.tags', fn($tq) => $tagLikeQuery($tq, $searchTags));
            });
        }
        // Case 3: Section + tag
        elseif (!empty($sectionNames) && !empty($searchTags)) {
            $sectionsQuery->where(function ($q) use ($sectionNames, $searchTags, $tagLikeQuery) {
                $q->whereIn('name', $sectionNames)
                    ->orWhere(function ($qq) use ($sectionNames, $searchTags, $tagLikeQuery) {
                        $qq->whereNotIn('name', $sectionNames)
                            ->where(function ($tagQ) use ($searchTags, $tagLikeQuery) {
                                $tagQ->whereHas('assets.tags', fn($tq) => $tagLikeQuery($tq, $searchTags))
                                    ->orWhereHas('subfolders.assets.tags', fn($tq) => $tagLikeQuery($tq, $searchTags));
                            });
                    });
            });
        }
        // Fallback: normal keyword search
        elseif (!empty($search)) {
            $sectionsQuery->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhereHas('subfolders', fn($q2) => $q2->where('name', 'ilike', "{$search}%"))
                    ->orWhereHas('assets', fn($q2) => $this->applyAssetSearchFilter($q2, $search))
                    ->orWhereHas('subfolders.assets', fn($q2) => $this->applyAssetSearchFilter($q2, $search));
            });
        }

        /**
         * ----------------------------------------------------
         * 3. Eager load assets & subfolders
         * ----------------------------------------------------
         */
        $sectionsQuery->with([
            'assets' => function ($query) use ($sectionNames, $searchTags, $extension, $tagIds, $labelIds, $collectionIds, $upload_on, $startDate, $endDate, $search, $tagLikeQuery) {
                $query->where('is_completed', true)->whereNull('assets.deleted_at');

                // Only tag filter
                if (empty($sectionNames) && !empty($searchTags)) {
                    $query->whereHas('tags', fn($tq) => $tagLikeQuery($tq, $searchTags));
                }

                // Section + tag filter
                if (!empty($sectionNames) && !empty($searchTags)) {
                    $query->where(function ($q) use ($sectionNames, $searchTags, $tagLikeQuery) {
                        $q->whereHas('sections', fn($sq) => $sq->whereIn('name', $sectionNames))
                            ->orWhere(function ($taggedQ) use ($sectionNames, $searchTags, $tagLikeQuery) {
                                $taggedQ->whereHas('sections', fn($sq) => $sq->whereNotIn('name', $sectionNames))
                                    ->whereHas('tags', fn($tq) => $tagLikeQuery($tq, $searchTags))
                                    ->where('is_completed', true)
                                    ->whereNull('assets.deleted_at');
                            });
                    });
                }

                // Always apply asset filters (including section-only case)
                $this->applyAssetFilters(
                    $query,
                    $extension,
                    $tagIds,
                    $labelIds,
                    $collectionIds,
                    $upload_on,
                    $startDate,
                    $endDate,
                    (empty($sectionNames) && empty($searchTags)) ? $search : null
                );
            },

            'subfolders.assets' => function ($query) use ($sectionNames, $searchTags, $extension, $tagIds, $labelIds, $collectionIds, $upload_on, $startDate, $endDate, $search, $recentUpload, $tagLikeQuery) {
                $query->where('is_completed', true)->whereNull('assets.deleted_at');

                // Only tag filter
                if (empty($sectionNames) && !empty($searchTags)) {
                    $query->whereHas('tags', fn($tq) => $tagLikeQuery($tq, $searchTags));
                }

                // Section + tag filter
                if (!empty($sectionNames) && !empty($searchTags)) {
                    $query->where(function ($q) use ($sectionNames, $searchTags, $tagLikeQuery) {
                        $q->whereHas('subfolder.sections', fn($sq) => $sq->whereIn('name', $sectionNames))
                            ->orWhere(function ($taggedQ) use ($sectionNames, $searchTags, $tagLikeQuery) {
                                $taggedQ->whereHas('subfolder.sections', fn($sq) => $sq->whereNotIn('name', $sectionNames))
                                    ->whereHas('tags', fn($tq) => $tagLikeQuery($tq, $searchTags))
                                    ->where('is_completed', true)
                                    ->whereNull('assets.deleted_at');
                            });
                    });
                }

                // Always apply asset filters (including section-only case)
                $this->applyAssetFilters(
                    $query,
                    $extension,
                    $tagIds,
                    $labelIds,
                    $collectionIds,
                    $upload_on,
                    $startDate,
                    $endDate,
                    (empty($sectionNames) && empty($searchTags)) ? $search : null,
                    $recentUpload
                );
            }
        ]);

        /**
         * ----------------------------------------------------
         * 4. Apply section-level filters
         * ----------------------------------------------------
         */
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
        if (!empty($collectionIds)) {
            $sectionsQuery->whereHas('assets.collections', function ($q) use ($collectionIds) {
                $q->whereIn('collections.id', $collectionIds);
            })->orWhereHas('subfolders.assets.collections', function ($q) use ($collectionIds) {
                $q->whereIn('collections.id', $collectionIds);
            });
        }

        if (!empty($upload_on)) {
            $sectionsQuery->where(function ($q) use ($upload_on, $startDate, $endDate) {
                $q->whereHas('assets', fn($sq) => $this->applyUploadOnFilter($sq, $upload_on, $startDate, $endDate))
                    ->orWhereHas('subfolders.assets', fn($sq) => $this->applyUploadOnFilter($sq, $upload_on, $startDate, $endDate));
            });
        }

        /**
         * ----------------------------------------------------
         * 5. Final results
         * ----------------------------------------------------
         */
        $sections = $sectionsQuery->orderBy('position', 'asc')->where('id',$sectionId)->get();

        /**
         * ----------------------------------------------------
         * 6. Post-process results
         * ----------------------------------------------------
         */
        $filteredSections = $sections->map(function ($section) use ($filtersApplied) {
            $section->assets = $section->assets ?? collect();
            $section->filtered_section_assets_count = $section->assets->count();

            $section->subfolders = $section->subfolders->map(function ($subfolder) {
                $subfolder->assets = $subfolder->assets ?? collect();
                $subfolder->filtered_subfolder_assets_count = $subfolder->assets->count();
                $subfolder->no_assets_found = $subfolder->assets->isEmpty();
                return $subfolder;
            })->values();

            $section->filtered_section_folder_count = $section->subfolders->count();
            return $section;
        });

        return $filteredSections;
    }
}
