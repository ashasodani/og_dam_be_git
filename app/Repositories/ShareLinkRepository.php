<?php
namespace App\Repositories;

use App\Models\ShareLinks;
use App\Models\Sections;
use App\Models\SubFolders;
use App\Models\Assets;
use App\Repositories\BaseRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;


/**
 * Class ShareLinkRepository
 *
 * Repository class for interacting with the `ShareLinks` model.
 *
 * @package App\Repositories
 */
class ShareLinkRepository extends BaseRepository
{
    /**
     * ShareLinkRepository constructor.
     *
     * @param ShareLinks $model The underlying model for the repository.
     */
    public function __construct(ShareLinks $model)
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
    public function sharelinkWithWorkspace($request): LengthAwarePaginator
    {
        $relation = $request->has('include') ? $request->get('include') : ['users'];
        $perPage = request()->input('per_page', 10);
        $slug = request()->input('slug');
        $append = $request->all();
        $append['per_page'] = $perPage??10;
        $search= $append['search']??'';
        $columns = ['*'];
        $searchColumns = ['name'];
        $append['sort_by'] = $append['sort_by']??'created_at';
        $append['sort_order'] = $append['sort_order']??'desc';
        $allowedSortFields = ['created_at', 'share_link_logs_count', 'assets_count'];
        //$created_by = false;
        $now           = now();
       // Sharelinks::where('expiry_date', '<', now())->where('status', '!=', 0)->update(['status' => 0]);
       
        $query = ShareLinks::with(['workspaces' => function($query) use ($slug) {
            $query->where('slug', $slug);
        }])->withCount(['shareLinkLogs', 'assets'])
        ->whereHas('workspaces', function ($query) use ($slug) {
            $query->where('slug', $slug);
        });
      
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status === "1" ? "1" : "0");
        }
        if ($request->filled('createdBy')) {
           // dd($request->createdBy);
            $query->where('create_by', (int)$request->createdBy);
        }
        if(request()->input('created_by_me') === "true"){
            $loginUser = Auth::user()->id;
            $query->where('create_by', $loginUser);
        }
        
        if ($request->filled('created_on') && $request->created_on !== 'all') {
            switch ($request->created_on) {
                case '30min':
                    $query->where('created_at', '>=', $now->copy()->subMinutes(30));
                    break;
                case '24hours':
                    $query->where('created_at', '>=', $now->copy()->subHours(24));
                    break;
                case 'recent_created':
                    $query->where('created_at', '>=', $now->copy()->subHours(24));
                    break;
                case '7days':
                    $query->where('created_at', '>=', $now->copy()->subDays(7));
                    break;
                case 'range':
                    if ($request->filled('created_from') && $request->filled('created_to')) {
                      
                        $query->whereBetween('created_at', [
                            Carbon::parse($request->created_from)->startOfDay(),
                            Carbon::parse($request->created_to)->endOfDay(),
                        ]);
                    }
                    break;
            }
        }
        if ($request->filled('expiry_date') && $request->expiry_date !== 'all') {
            switch ($request->expiry_date) {
                case '30min':
                    $query->where('expiry_date', '>=', $now->copy()->subMinutes(30));
                    break;
                case '24hours':
                    $query->where('expiry_date', '>=', $now->copy()->subHours(24));
                    break;
                case 'recent_created':
                    $query->where('expiry_date', '>=', $now->copy()->subHours(24));
                    break;
                case '7days':
                    $query->where('expiry_date', '>=', $now->copy()->subDays(7));
                    break;
                case 'range':
                    if ($request->filled('expiry_from') && $request->filled('expiry_to')) {
                        $query->whereBetween('expiry_date', [
                            Carbon::parse($request->expiry_from)->startOfDay(),
                            Carbon::parse($request->expiry_to)->endOfDay(),
                        ]);
                    }
                    break;
            }
        }

        $query->when($search, function ($query, $search) use ($searchColumns) {
            $query->where(function ($q) use ($search, $searchColumns) {
                foreach ($searchColumns as $column) {
                    $q->orWhere($column, 'ILIKE', '%' . $search . '%');
                }
            });
        });
        $sortBy = in_array($append['sort_by'], $allowedSortFields) ? $append['sort_by'] : 'created_at';
        $sortOrder = in_array(strtolower($append['sort_order']), ['asc', 'desc']) ? strtolower($append['sort_order']) : 'desc';

        $data = $query->orderBy($sortBy, $sortOrder)
              ->paginate($append['per_page']);
       // dd($data);
        //$data= $query->orderBy($append['sort_by'], $append['sort_order'])->paginate($append['per_page']);
        return $data;
    }

    public function findByUrl($url)
    {
        return $this->model->where('url', $url)->first();
    }

    public function getAssetIds($data)
    {
        $assetIdsFromRequest = collect(request('asset_id', []));
        $assetIdsFromSubfolders=[];
        if (! empty($data['subfolder_id']) && is_array($data['subfolder_id'])) {
           // $assetIdsFromSubfolders = SubFolders::with('assets')->whereIn('id', $data['subfolder_id']) ->pluck('asset_id');
            $assetIdsFromSubfolders = \DB::table('subfolder_assets')
            ->whereIn('sub_folder_id', $data['subfolder_id'])
            ->pluck('asset_id')
            ->unique()
            ->values();
            

        }
         $mergedAssetIds = $assetIdsFromRequest
                ->merge($assetIdsFromSubfolders)
                ->unique()
                ->values();
        return $mergedAssetIds;
    }
    public function getSelectedSharelinkData($Id, $request){
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
        $assetId = $this->findById($Id, ['*'], ['assets']);
        $assetIds = [];
        $assetIds = collect($assetId->assets)->pluck('id')->toArray();

        $sectionSearchOnly = false;
        $folderSearchOnly = false;
        $assetSearchOnly = false;
        $filtersApplied = !empty($extension) || !empty($tagIds) || !empty($labelIds) || !empty($collectionIds) || !empty($upload_on) || (!empty($startDate) && !empty($endDate));

        if (!empty($search)) {
            $matchedSections = Sections::whereHas('assets.sharelinks', function ($query) use ($Id) {
                $query->where('share_link_id', $Id);
            })->where('name', 'ilike', "%{$search}%")->with('assets')->pluck('id');

            $matchedFolders = SubFolders::whereHas('assets.sharelinks', function ($query) use ($Id) {
                $query->where('share_link_id', $Id);
            })->where('name', 'ilike', "%{$search}%")->with('assets')->pluck('id');
            $matchedAssets = Assets::whereHas('sharelinks', function ($query) use ($Id) {
                $query->where('share_link_id', $Id);
            })
                ->where('name', 'ilike', "{$search}%")
                ->orWhere('filename', 'ilike', "{$search}%")
                ->orWhere('description', 'ilike', "{$search}%")
                 ->orWhere('asset_key', 'ilike', "{$search}%")
                ->orWhere('extension', 'ilike', "{$search}%")
                ->orWhereHas('tags', fn($tq) => $tq->where('name', 'ilike', "{$search}%"))
                ->orWhereHas('labels', fn($lq) => $lq->where('name', 'ilike', "{$search}%"))
                ->orWhereHas('collections', fn($lq) => $lq->where('name', 'ilike', "{$search}%"))
                ->pluck('id');


            $sectionSearchOnly = $matchedSections->isNotEmpty() && $matchedFolders->isEmpty() && $matchedAssets->isEmpty();
            $folderSearchOnly = $matchedFolders->isNotEmpty() && $matchedSections->isEmpty() && $matchedAssets->isEmpty();
            $assetSearchOnly = $matchedAssets->isNotEmpty() && $matchedSections->isEmpty() && $matchedFolders->isEmpty();
        }

        $sections = Sections::where(function ($query) use ($Id) {
            $query->whereHas('assets.sharelinks', function ($q) use ($Id) {
                $q->where('share_link_id', $Id);
            })
            ->orWhereHas('subfolders.assets.sharelinks', function ($q) use ($Id) {
                $q->where('share_link_id', $Id);
            });
        })->when(!empty($search), function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhereHas('subfolders', fn($q2) => $q2->where('name', 'ilike', "{$search}%"))
                    ->orWhereHas('assets', fn($q2) => $this->applyAssetSearchFilter($q2, $search))
                    ->orWhereHas('subfolders.assets', fn($q2) => $this->applyAssetSearchFilter($q2, $search));
            });
        })
            ->with([
                'assets' => function ($query) use ($assetIds, $sectionSearchOnly, $extension, $tagIds, $labelIds, $collectionIds, $upload_on, $startDate, $endDate, $search) {

                    $query->where('is_completed', true)->whereNull('assets.deleted_at');
                    $query->whereIn('asset_id', $assetIds);
                    if (!$sectionSearchOnly) {
                        $this->applyAssetFilters($query, $extension, $tagIds, $labelIds, $collectionIds, $upload_on, $startDate, $endDate, $search);
                    }
                },
                'subfolders' => function ($query) use ($search, $folderSearchOnly) {
                    if ($folderSearchOnly) {
                        $query->where('name', 'ilike', "{$search}%");
                    }
                },
                'subfolders.assets' => function ($query) use ($assetIds, $sectionSearchOnly, $folderSearchOnly, $extension, $tagIds, $labelIds, $collectionIds, $upload_on, $startDate, $endDate, $search, $recentUpload) {
                    $query->where('is_completed', true)->whereNull('assets.deleted_at');
                    $query->whereIn('asset_id', $assetIds);
                    if ($folderSearchOnly) {
                        return;
                    }

                    if (!$sectionSearchOnly) {
                        $this->applyAssetFilters($query, $extension, $tagIds, $labelIds, $collectionIds, $upload_on, $startDate, $endDate, $search, $recentUpload);
                    }
                }
            ])
            ->orderBy('position', 'desc')
            ->get();
                //dd($sections);
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
