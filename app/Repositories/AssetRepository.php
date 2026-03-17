<?php

namespace App\Repositories;

use App\Models\Assets;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;
use Imagick;

/**
 * Class AssestRepository
 *
 * Repository class for interacting with the `Assets` model.
 *
 * @package App\Repositories
 */
class AssetRepository extends BaseRepository
{
    /**
     * AssetRepository constructor.
     *
     * @param Assets $model The underlying model for the repository.
     */
    public function __construct(Assets $model)
    {
        $this->model = $model;
    }
    /**
     * Retrieve a paginated collection of collections which have a workspace with a given slug.
     *
     * @param Request $request The request object.
     *
     * @return Collection
     */
    public function getAssetData($request): Collection
    {
        $include = $request->has('include') ? json_decode($request->get('include')) : [];

        $relation = $include;
        // dd($relation);
        $perPage = request()->input('per_page');
        $slug = request()->input('slug');
        $append = $request->all();
        $append['per_page'] = $perPage ?? 100;
        $columns = ['*'];
        $searchColumns = ['name', 'description', 'asset_key', 'extension', 'hex', 'rgb', 'cmyk', 'pantagone_coated', 'pantagone_uncoated'];
        $search = $append['search'] ?? '';
        $append['sort_by'] = $append['sort_by'] ?? 'created_at';
        $append['sort_order'] = $append['sort_order'] ?? 'desc';
        //dd($relation,$tags);
        $query = Assets::whereHas('workspaces', function ($query) use ($slug) {
            $query->where('slug', $slug);
        });

        $query->when($search, function ($query, $search) use ($searchColumns) {
            $query->where(function ($q) use ($search, $searchColumns) {
                foreach ($searchColumns as $column) {
                    $q->orWhere($column, 'ILIKE', '%' . $search . '%');
                }
            });
        });
        if (!empty($request->input('tags'))) {
            $tags = json_decode($request->input('tags'));
            $query->whereHas('tags', function ($query) use ($tags) {
                $query->whereIn('tag_id', $tags);
            });
        }
        if (!empty($request->input('labels'))) {
            $labels = json_decode($request->input('labels'));
            $query->whereHas('labels', function ($query) use ($labels) {
                $query->whereIn('label_id', $labels);
            });
        }

        if (!empty($request->input('section_id'))) {
            $section_id = $request->input('section_id');
            $query->whereHas('sections', function ($query) use ($section_id) {
                $query->where('section_id', $section_id);
            });
        }
        if (!empty($request->input('subfolder_id'))) {
            $subfolder_id = $request->input('subfolder_id');
            $query->whereHas('subfolders', function ($query) use ($subfolder_id) {
                $query->where('sub_folder_id', $subfolder_id);
            });
        }

        if (!empty($request->input('asset_type'))) {
            $assetType = json_decode($request->get('asset_type'));
            $query->where(function ($query) use ($assetType) {
                foreach ($assetType as $ext) {
                    $query->orWhere('extension', $ext);
                }
            });
        }


        if ($request->input('recent_upload')) {
            $query->where('recent_upload', true);
        }
        if (!empty($request->input('upload_on'))) {
            switch ($request->input('upload_on')) {
                case 'last_30_minutes':
                    $query->where('created_at', '>=', Carbon::now()->subMinutes(30));
                    break;

                case 'past_24_hours':
                    $query->where('created_at', '>=', Carbon::now()->subDay());
                    break;

                case 'last_7_days':
                    $query->where('created_at', '>=', Carbon::now()->subDays(7));
                    break;
                case 'recently_upload':
                    $query->where('assets.recent_upload', true);
                    break;

                case 'custom_range':
                    $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
                    $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                    break;

                default:
                    // No filter applied or return an error
                    break;
            }
        }
        $query->with($relation)->where('is_completed', true);
        $data = $query->get();
        // dd($data);
        return $data;
    }


    public function findByKey($assetKey)
    {
        $asset =  Assets::where('asset_key', $assetKey)->first();
        return $asset;
    }

    public function getAssetIds($assetIds, $subfolderIds)
    {
        $assetIdsFromRequest = collect($assetIds);
        $assetIdsFromSubfolders = [];
        if (! empty($subfolderIds) && is_array($subfolderIds)) {
            // $assetIdsFromSubfolders = SubFolders::with('assets')->whereIn('id', $data['subfolder_id']) ->pluck('asset_id');
            $assetIdsFromSubfolders = \DB::table('subfolder_assets')
                ->whereIn('sub_folder_id', $subfolderIds)
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

    public function assetImageResize($originalImage, $ext, $height = 162, $width = 262)
    {
        $filePath = $originalImage;
        /* swith case */
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        switch ($ext) {
            case 'jpg':
                $thumbnail = $this->imageResize($filePath, $height, $width);
                break;
            case 'jpeg':
                $thumbnail = $this->imageResize($filePath, $height, $width);
                break;
            case 'png':
                $thumbnail = $this->imageResize($filePath, $height, $width);
                break;
            case 'gif':
                $thumbnail = $this->imageResize($filePath, $height, $width);
                break;
            case 'wbmp':
                $thumbnail = $this->imageResize($filePath, $height, $width);
                break;
            case 'webp':
                $thumbnail = $this->imageResize($filePath, $height, $width);
                break;
            case 'pdf':
                // PDF to image
                $tmpDir = storage_path('app/public/thumbnails');
                if (!is_dir($tmpDir)) {
                    mkdir($tmpDir, 0755, true);
                }

                if (!is_writable($tmpDir)) {
                    throw new \Exception("Directory not writable: {$tmpDir}");
                }
                $tmpPng = $tmpDir . '/' . uniqid('thumb_', true) . '.png';
                $imagick = new Imagick();
                $imagick->setResolution(150, 150);
                $imagick->readImage($filePath . '[0]');
                $imagick->setImageFormat('png');
                $imagick->writeImage($tmpPng);
                $imagick->clear();
                $imagick->destroy();
                $thumbPath = storage_path('app/public/thumbnails/' . uniqid('final_') . '.webp');
                $thumbnail = $this->imageResize($tmpPng, $height, $width);
                @unlink($tmpPng);
                break;
            case 'eps':
                // EPS to image
                $tmpDir = storage_path('app/public/thumbnails');
                if (!is_dir($tmpDir)) {
                    mkdir($tmpDir, 0755, true);
                }

                if (!is_writable($tmpDir)) {
                    throw new \Exception("Directory not writable: {$tmpDir}");
                }
                $tmpPng = $tmpDir . '/' . uniqid('thumb_', true) . '.png';
                $imagick = new Imagick();
                $imagick->setResolution(150, 150);
                $imagick->readImage($filePath . '[0]');
                $imagick->setImageFormat('png');
                $imagick->writeImage($tmpPng);
                $imagick->clear();
                $imagick->destroy();
                $thumbPath = storage_path('app/public/thumbnails/' . uniqid('final_') . '.webp');
                $thumbnail = $this->imageResize($tmpPng, $height, $width);
                @unlink($tmpPng);
                break;

            case 'avi':
            case 'mov':
            case 'mp4':
                $tmpDir = storage_path('app/public/thumbnails');
                if (!is_dir($tmpDir)) {
                    mkdir($tmpDir, 0755, true);
                }

                if (!is_writable($tmpDir)) {
                    throw new \Exception("Directory not writable: {$tmpDir}");
                }

                $tmpFrame = $tmpDir . '/' . uniqid('thumb_', true) . '.jpg';

                // Extract frame using FFMpeg
                $ffmpeg = \FFMpeg\FFMpeg::create([
                    'ffmpeg.binaries'  => '/usr/bin/ffmpeg',
                    'ffprobe.binaries' => '/usr/bin/ffprobe',
                    'timeout'          => 3600, // seconds
                    'ffmpeg.threads'   => 12,
                ]);

                $video = $ffmpeg->open($filePath);
                $frame = $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds(1)); // get frame at 1 second
                $frame->save($tmpFrame);

                $thumbnail = $this->imageResize($tmpFrame, $height, $width);
                @unlink($tmpFrame);
                break;
            case 'docx':
            case 'pptx':

            default:
                throw new \Exception("Unsupported file type: .$ext");
        }
        return $thumbnail;
        /* swith case */
    }
}
