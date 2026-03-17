<?php

namespace App\Repositories;

use App\Models\SubFolders;
use App\Repositories\BaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Sections;
use App\Models\Assets;
use App\Models\ShareLinks;
use App\Models\Collections;
use Carbon\Carbon;

/**
 * Class SubFolderRepository
 *
 * Repository class for interacting with the `SubFolders` model.
 *
 * @package App\Repositories
 */
class SubFolderRepository extends BaseRepository
{
    /**
     * SubFolderRepository constructor.
     *
     * @param SubFolders $model The underlying model for the repository.
     */
    public function __construct(SubFolders $model)
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
    public function subfolderWithWorkspace($request): LengthAwarePaginator
    {
        $relation = $request->has('include') ? $request->get('include') : [];
        $perPage = request()->input('per_page');
        $slug = request()->input('slug');
        $section_id = request()->input('section_id');
        $append = $request->all();
        $append['per_page'] = $perPage ?? 100;
        $columns = ['*'];
        $searchColumns = ['name'];
        $search = $append['search'] ?? '';
        $append['sort_by'] = $append['sort_by'] ?? 'created_at';
        $append['sort_order'] = $append['sort_order'] ?? 'desc';

        $query = SubFolders::whereHas('workspaces', function ($query) use ($slug) {
            $query->where('slug', $slug);
        })->whereHas('sections', function ($query) use ($section_id) {
            $query->where('section_id', $section_id);
        })->with($relation);
        $query->when($search, function ($query, $search) use ($searchColumns) {
            $query->where(function ($q) use ($search, $searchColumns) {
                foreach ($searchColumns as $column) {
                    $q->orWhere($column, 'LIKE', '%' . $search . '%');
                }
            });
        });
        $data = $query->orderBy($append['sort_by'], $append['sort_order'])->paginate($append['per_page']);
        return $data;
    }
    public function subfolderWithFilter($id, $request)
    {
        $search = trim($request->input('search'));
        $extension = collect(json_decode($request->input('asset_type')))->map(fn($ext) => strtolower($ext))->toArray();
        $tagIds = json_decode($request->input('tags'));
        $labelIds = json_decode($request->input('labels'));
        $upload_on = $request->input('upload_on');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $recentUpload = $request->input('recent_upload');
        $sharelinkId=null;
        $collectionId=null;
        if($request->has('share_slug')){
            $shreUrl = env('APP_FE_URL').'s/'.$request->input('share_slug')??null;
            $sharelinkData = ShareLinks::where('url',$shreUrl)->first();
            $sharelinkId = $sharelinkData->id??null;
        }
        if($request->has('collection_slug')){
            $colelctionData = Collections::where('slug',$request->input('collection_slug'))->first();
            $collectionId = $colelctionData->id??null;
             
        }
        
        $matchedAssets = collect();

        if (!empty($search)) {
            $matchedAssets = Assets::where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('filename', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%")
                    ->orWhere('extension', 'ilike', "%{$search}%")
                    ->orWhere('asset_key', 'ilike', "{$search}%")
                    ->orWhereHas('tags', fn($tq) => $tq->where('name', 'ilike', "%{$search}%"))
                    ->orWhereHas('labels', fn($lq) => $lq->where('name', 'ilike', "%{$search}%"));
            })
            ->pluck('id');
        }
        $subfolders = SubFolders::where('id', $id)->whereHas('assets', function ($query) use ($matchedAssets, $extension, $tagIds, $labelIds, $upload_on, $startDate, $endDate, $search, $recentUpload) {
            $query->where('assets.is_completed', true)
                ->whereNull('assets.deleted_at');

            if ($matchedAssets->isNotEmpty()) {
                $query->whereIn('assets.id', $matchedAssets);
            }
            $this->applyAssetFilters($query, $extension, $tagIds, $labelIds, [], $upload_on, $startDate, $endDate, $search, $recentUpload);
        })
            ->with([
                'assets' => function ($query) use ($sharelinkId,$collectionId,$matchedAssets, $extension, $tagIds, $labelIds, $upload_on, $startDate, $endDate, $search, $recentUpload) {
                    $query->where('assets.is_completed', true)
                        ->whereNull('assets.deleted_at');
                        if (!is_null($sharelinkId)) {
                            
                            $query = $query->whereHas('sharelinks', function ($q) use ($sharelinkId) {
                                $q->where('share_link_id', $sharelinkId);
                            });
                        }
                        
                        if (!is_null($collectionId)) {
                            $query=$query->whereHas('collections', function ($q) use ($collectionId) {
                                $q->where('collection_id', $collectionId);
                            });
                        }
                       

                    if ($matchedAssets->isNotEmpty()) {
                        $query->whereIn('assets.id', $matchedAssets);
                    }

                    $this->applyAssetFilters($query, $extension, $tagIds, $labelIds, [], $upload_on, $startDate, $endDate, $search, $recentUpload);
                }
            ])
            ->get();
        //  dd($subfolders);
        $filteredSubfolders = $subfolders->map(function ($subfolder) {
            //dd($subfolder);
            $subfolder->assets = $subfolder->assets ?? collect();
            $subfolder->filtered_subfolder_assets_count = $subfolder->assets->count();
            //dd($subfolder);
            return $subfolder;
        })->filter(fn($subfolder) => $subfolder->filtered_subfolder_assets_count > 0)->values();
        //dd($filteredSubfolders);
        if($filteredSubfolders->isEmpty()){
            return [];
        }else{
            return $filteredSubfolders[0];
        }   
        
    }

    public function applyAssetFilters($query, $extension, $tagIds, $labelIds, $collectionIds, $upload_on, $startDate, $endDate, $search, $recentUpload = null)
    {
        //dd($extension,$tagIds,$labelIds,$collectionIds,$upload_on,$startDate,$endDate,$search,$recentUpload);
        if (!empty($extension)) {
            $query->whereIn('extension', $extension);
        }

        if (!empty($recentUpload)) {
            $query->where('recent_upload', $recentUpload);
        }

        if (!empty($tagIds)) {
            $query->whereHas('tags', fn($q) => $q->whereIn('tags.id', $tagIds));
        }

        if (!empty($labelIds)) {
            $query->whereHas('labels', fn($q) => $q->whereIn('labels.id', $labelIds));
        }

        if (!empty($upload_on)) {
            switch ($upload_on) {
                case 'last_30_minutes':
                    $query->where('assets.created_at', '>=', now()->subMinutes(30));
                    break;
                case 'past_24_hours':
                    $query->where('assets.created_at', '>=', now()->subDay());
                    break;
                case 'last_7_days':
                    $query->where('assets.created_at', '>=', now()->subDays(7));
                    break;
                case 'recent_upload':
                    $query->where('assets.created_at', '>=', now()->subDay());
                    break;
                case 'range':
                    $start = Carbon::parse($startDate)->startOfDay();
                    $end = Carbon::parse($endDate)->endOfDay();
                    $query->whereBetween('assets.created_at', [$start, $end]);
                    break;
            }
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('filename', 'ilike', "%{$search}%")
                    ->orWhere('asset_key', 'ilike', "{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%")
                    ->orWhere('extension', 'ilike', "%{$search}%")
                    ->orWhereHas('tags', fn($tq) => $tq->where('name', 'ilike', "%{$search}%"))
                    ->orWhereHas('labels', fn($lq) => $lq->where('name', 'ilike', "%{$search}%"));
            });
        }

        return $query;
    }
}
