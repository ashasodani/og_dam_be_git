<?php

namespace App\Http\Controllers\API;

use App\Enums\PermissionEnum;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Requests\Asset\AssetCreateRequest;
use App\Http\Requests\Asset\AssetLabelUpdateRequest;
use App\Http\Requests\Asset\AssetLargeFileUploadRequest;
use App\Http\Requests\Asset\AssetTagUpdateRequest;
use App\Http\Requests\Asset\AssetThumbnailRequest;
use App\Http\Requests\Asset\AssetTypeCreateRequest;
use App\Http\Requests\Asset\AssetUpdateRequest;
use App\Http\Resources\AssetChunkResource;
use App\Http\Resources\AssetResource;
use App\Http\Resources\AssetThumbnailResource;
use App\Models\Assets;
use App\Services\AssetService;
use Aws\S3\S3Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use ZipStream\Option\Archive;
use ZipStream\ZipStream;
use Aws\CloudFront\CloudFrontClient;


class AssetController extends BaseController
{
    /**
     * @var AssetService The service for handling Asset operations.
     */
    protected $assetService;

    /**
     * @var permissionSlugs The slug for handling Asset operations.
     */
    protected $permissionSlugs;

    /**
     * AssetController constructor
     *
     * @param AssetService   $assetService   The service for handling Asset related operations.
     */
    public function __construct(AssetService $assetService)
    {
        $this->assetService    = $assetService;
        $this->moduleName      = trans("asset.module_name");
        $this->permissionSlugs = PermissionEnum::Slugs->getAll();
        //$this->authorizeResource(Assets::class, 'Asset');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Mixed
     */
    public function index(Request $request): mixed
    {
        $AssetData = $this->assetService->getAssetCollection($request);
        try {
            return $this->successResponse(
                AssetResource::collection($AssetData),
                trans(
                    'common.fetch_successfully',
                    ['module' => $this->moduleName]
                )
            );
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(AssetCreateRequest $request): JsonResponse
    {
        //   $this->authorize($this->permissionSlugs["Assets"]["create"], Assets::class);
        try {
            $input = $request->all();
            $Asset = $this->assetService->createAsset($input);
            return $this->successResponse(
                new AssetResource($Asset),
                trans(
                    'common.create_successfully',
                    ['module' => $this->moduleName]
                )
            );
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Display the specified Asset.
     *
     * @param int $AssetId The ID of the Asset to view.
     *
     * @return Mixed
     */
    public function show(int $Id, Request $request): mixed
    {
        try {
            $Asset = $this->assetService->findByAssetId($Id, $request);
            return $this->successResponse(
                new AssetResource($Asset),
                trans(
                    'common.fetch_successfully',
                    ['module' => $this->moduleName]
                )
            );
        } catch (\Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }
    /**
     * Display the specified Asset.
     *
     * @param int $AssetId The ID of the Asset to view.
     *
     * @return Mixed
     */
    public function showAssetKey(string $assetKey, Request $request): mixed
    {
        try {
            $Asset = $this->assetService->findByAssetsKey($assetKey, $request);
            return $this->successResponse(
                new AssetResource($Asset),
                trans(
                    'common.fetch_successfully',
                    ['module' => $this->moduleName]
                )
            );
        } catch (\Throwable $throwable) {
            dd($throwable);
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Update the Asset in storage.
     *
     * @param int  $id  of the Asset.
     * @param AssetUpdateRequest $request The request containing the validated data for updating Asset.
     *
     * @return JsonResponse
     */
    public function assetUpdate(int $AssetId, AssetUpdateRequest $AssetRequest): JsonResponse
    {
        try {
            $data         = $AssetRequest->validated();
            $Asset        = $this->assetService->updateAsset($AssetId, $AssetRequest);
            $Asset->type  = 'asset_update';
            $Asset->title = 'Asset Updated';
            $notifyUser   = $this->assetService->notifyUserAssetUpdate($Asset);
            return $this->successResponse(
                new AssetResource($Asset),
                trans(
                    'common.update_successfully',
                    ['module' => $this->moduleName]
                )
            );
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }
    public function assetViewTag(int $AssetId, AssetTagUpdateRequest $AssetRequest): JsonResponse
    {
        try {
            $data  = $AssetRequest->validated();
            $Asset = $this->assetService->updateTagAsset($AssetId, $AssetRequest);
            return $this->successResponse(
                new AssetResource($Asset),
                trans(
                    'common.update_successfully',
                    ['module' => $this->moduleName]
                )
            );
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }
    public function assetViewLabel(int $AssetId, AssetLabelUpdateRequest $AssetRequest): JsonResponse
    {
        try {
            $data  = $AssetRequest->validated();
            $Asset = $this->assetService->updateLabelAsset($AssetId, $AssetRequest);
            return $this->successResponse(
                new AssetResource($Asset),
                trans(
                    'common.update_successfully',
                    ['module' => $this->moduleName]
                )
            );
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Remove the specified Asset from storage.
     *
     * @param Request $request The request object.
     * @param int  $Asset id of the Asset.
     *
     * @return Mixed
     */
    public function destroy(Request $request, int $AssetId): Mixed
    {
        try {
            $workspace = $this->assetService->deleteAssetById($AssetId);
            if ($workspace) {
                return $this->successResponse(
                    [],
                    trans(
                        'common.delete_successfully',
                        ['module' => $this->moduleName]
                    )
                );
            }
            return $this->sendError('Something Wrong.', trans('common.something_wrong'), 401);
        } catch (\Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Generate thumbnails for the specified Asset.
     *
     * @param AssetThumbnailRequest $request The request containing the data necessary for generating thumbnails.
     *
     * @return JsonResponse
     */

    public function assetThumbnails(AssetThumbnailRequest $request): JsonResponse
    {
        try {
            $Asset = $this->assetService->createThumbnails($request);
            return $this->successResponse(
                new AssetThumbnailResource($Asset),
                trans(
                    'common.create_successfully',
                    ['module' => $this->moduleName]
                )
            );
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    public function uploadChunk(AssetLargeFileUploadRequest $request, $AssetId = null)
    {
        try {
            $Asset = $this->assetService->chunkAssetUpload($request, $AssetId);
            return $this->successResponse(
                new AssetChunkResource($Asset),
                trans(
                    'common.create_successfully',
                    ['module' => $this->moduleName]
                )
            );
        } catch (Throwable $throwable) {
            Log::error('Chunk upload error: ' . $throwable->getMessage(), [
                'exception' => $throwable,
                'file'      => $throwable->getFile(),
                'line'      => $throwable->getLine(),
                'trace'     => $throwable->getTraceAsString(),
            ]);
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }
    public function uploadChunks(AssetLargeFileUploadRequest $request, $AssetId = null)
    {
        try {
            $Asset = $this->assetService->chunkAssetUpload($request, $AssetId);
            return $this->successResponse(
                new AssetChunkResource($Asset),
                trans(
                    'common.create_successfully',
                    ['module' => $this->moduleName]
                )
            );
        } catch (Throwable $throwable) {
            Log::error('Something went wrong: ' . $throwable->getMessage(), [
                'exception' => $throwable,
                'file'      => $throwable->getFile(),
                'line'      => $throwable->getLine(),
            ]);
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    public function assetTypeCreate(AssetTypeCreateRequest $request)
    {

        try {
            $Asset = $this->assetService->createTypeAsset($request);
            return $this->successResponse(
                new AssetResource($Asset),
                trans(
                    'common.create_successfully',
                    ['module' => $this->moduleName]
                )
            );
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }
    /**
     * This method is used to view the asset.
     *
     * @param Request $request The request containing the asset_id.
     *
     * @return JsonResponse
     */
    // public function assetViewer(string $assetKey, Request $request)
    // {
    //     try {
    //         $Asset = $this->assetService->findByAssetKey($assetKey, $request);

    //         $asset_url = isset($Asset->asset_url) ? config('deegest.s3_bucket_url') . $Asset->asset_url : "";
    //         $disk = Storage::disk('s3');
    //         $path = $Asset->asset_url;

    //         if ($Asset->extension == 'pdf') {
    //              $stream = $disk->readStream($path);
    //             return response()->stream(function () use ($stream) {
    //                 fpassthru($stream);
    //             }, 200, [
    //                 'Content-Type' => 'application/pdf',
    //                 'Content-Disposition' => 'inline; filename="' . $path . '"',
    //                 'Access-Control-Allow-Origin' => '*', // Replace * with your domain in prod
    //                 'Access-Control-Allow-Methods' => 'GET, OPTIONS',
    //                 'Cache-Control' => 'no-cache, must-revalidate',
    //                 'Content-Length' => $disk->size($path),
    //             ]);
    //         }

    //         return redirect(
    //             Storage::disk('s3')->temporaryUrl(
    //                 $path,
    //                 now()->addMinutes(30),
    //                 [
    //                     'ResponseContentDisposition' => 'inline', // or 'attachment; filename="video.mp4"' for download
    //                 ]
    //             )
    //         );
    //         // dd($disk,$path);
    //         // if (!$disk->exists($path)) {
    //         //     abort(404, 'File not found.');
    //         // }

    //         // $mimeType = $disk->mimeType($path);
    //         // $fileName = basename($path);
    //         // $stream = $disk->readStream($path);
    //         // $fileSize = $disk->size($path);

    //         // if (!$stream) {
    //         //     abort(500, 'Could not open stream from S3');
    //         // }
    //         // return response()->stream(function () use ($stream) {
    //         //     while (!feof($stream)) {
    //         //         echo fread($stream, 1024 * 17); // read in chunks
    //         //         ob_flush();
    //         //         flush();
    //         //     }
    //         //     fclose($stream);
    //         // }, 200, [
    //         //     'Content-Type' => $mimeType,
    //         //     'Content-Disposition' => 'inline; filename="' . $fileName . '"',
    //         //     'Content-Length' => $fileSize,
    //         //     'Accept-Ranges' => 'bytes',
    //         //     'Cache-Control' => 'no-cache',
    //         // ]);

    //         // return new StreamedResponse(function () use ($disk, $path) {
    //         //     $stream = $disk->readStream($path);
    //         //     fpassthru($stream);
    //         //     fclose($stream);
    //         // }, 200, [
    //         //     'Content-Type' => $mimeType,
    //         //     'Content-Disposition' => "inline; filename=\"{$fileName}\"",
    //         //     'Cache-Control' => 'no-cache, no-store, must-revalidate',
    //         // ]);
    //     } catch (\Throwable $throwable) {
    //         report($throwable);
    //         return response()->json(['error' => $throwable->getMessage()], 500);
    //     }
    // }

    public function assetViewer(string $assetKey, Request $request)
    {
        try {
            $Asset = $this->assetService->findByAssetKey($assetKey, $request);

            if (! $Asset || ! $Asset->asset_url) {
                return response()->json(['error' => 'Asset not found'], 404);
            }

            $disk     = Storage::disk('s3');
            $path     = $Asset->asset_url;
            $fullPath = config('deegest.s3_bucket_url') . $path;

            if ($Asset->extension === 'pdf') {
                if (! $disk->exists($path)) {
                    return response()->json(['error' => 'File not found'], 404);
                }

                $size  = $disk->size($path);
                $range = $request->header('Range');

                if ($range) {
                    // Handle byte range
                    preg_match('/bytes=(\d+)-(\d*)/', $range, $matches);
                    $start  = intval($matches[1]);
                    $end    = $matches[2] !== '' ? intval($matches[2]) : $size - 1;
                    $length = $end - $start + 1;

                    return response()->stream(function () use ($disk, $path, $start, $length) {
                        $stream = $disk->readStream($path);
                        fseek($stream, $start);
                        echo fread($stream, $length);
                        fclose($stream);
                    }, 206, [
                        'Content-Type'                => 'application/pdf',
                        'Content-Length'              => $length,
                        'Content-Range'               => "bytes $start-$end/$size",
                        'Accept-Ranges'               => 'bytes',
                        'Content-Disposition'         => 'inline; filename="' . basename($path) . '"',
                        'Access-Control-Allow-Origin' => '*',
                    ]);
                } else {
                    // No range, serve full file
                    $stream = $disk->readStream($path);
                    return response()->stream(function () use ($stream) {
                        fpassthru($stream);
                    }, 200, [
                        'Content-Type'                => 'application/pdf',
                        'Content-Length'              => $size,
                        'Accept-Ranges'               => 'bytes',
                        'Content-Disposition'         => 'inline; filename="' . basename($path) . '"',
                        'Access-Control-Allow-Origin' => '*',
                    ]);
                }
            }

            // For non-PDFs, redirect with signed URL
            return redirect(
                Storage::disk('s3')->temporaryUrl(
                    $path,
                    now()->addMinutes(60),
                    [
                        'ResponseContentDisposition' => 'inline',
                    ]
                )
            );
        } catch (\Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * This method is used to view the asset.
     *
     * @param Request $request The request containing the asset_id.
     *
     * @return JsonResponse
     */
    public function assetViewerVideo(string $assetKey, Request $request)
    {
        try {
            $Asset     = $this->assetService->findByAssetKey($assetKey, $request);
            $asset_url = isset($Asset->asset_url) ? config('deegest.s3_bucket_url') . $Asset->asset_url : "";
            $disk      = Storage::disk('s3');
            $path      = $Asset->asset_url;
            $path      = $Asset->asset_url;
            if (! $disk->exists($path)) {
                return response()->json(['error' => 'File not found.'], 404);
            }

            $fileSize = $disk->size($path);
            $mimeType = $disk->mimeType($path);

            $start = 0;
            $end   = $fileSize - 1;

            if ($request->headers->has('Range')) {
                if (preg_match('/bytes=(\d+)-(\d*)/', $request->header('Range'), $matches)) {
                    $start = intval($matches[1]);
                    if (isset($matches[2]) && $matches[2] !== '') {
                        $end = intval($matches[2]);
                    }
                }
            }

            // Validate range
            if ($start > $end || $start >= $fileSize) {
                return response()->json(['error' => 'Invalid range.'], 416);
            }

            $length = $end - $start + 1;

            $headers = [
                'Content-Type'   => $mimeType,
                'Content-Length' => $length,
                'Content-Range'  => "bytes $start-$end/$fileSize",
                'Accept-Ranges'  => 'bytes',
            ];

            $response = new StreamedResponse(function () use ($disk, $path, $start, $length) {
                $stream = $disk->readStream($path);
                $offset = 0;
                $sent   = 0;

                while (! feof($stream) && $sent < $length) {
                    $buffer  = fread($stream, 8192);
                    $readLen = strlen($buffer);

                    if ($offset + $readLen < $start) {
                        $offset += $readLen;
                        continue;
                    }

                    if ($offset < $start) {
                        $buffer = substr($buffer, $start - $offset);
                        $offset = $start;
                    }

                    $remaining = $length - $sent;
                    $buffer    = substr($buffer, 0, $remaining);

                    echo $buffer;
                    flush();
                    $sent += strlen($buffer);
                    $offset += $readLen;
                }

                fclose($stream);
            }, $request->header('Range') ? 206 : 200, $headers);

            return $response;
        } catch (\Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * This method is used to view the asset.
     *
     * @param Request $request The request containing the asset_id.
     *
     * @return JsonResponse
     */
    public function assetVideoViewer(string $assetKey, Request $request)
    {
        try {
            $Asset     = $this->assetService->findByAssetKey($assetKey, $request);
            $asset_url = isset($Asset->asset_url) ? config('deegest.s3_bucket_url') . $Asset->asset_url : "";

            $disk      = Storage::disk('s3');
            $path      = $Asset->asset_url;

            /* stream video start */
            $filename = $path;

            if (! $disk->exists($path)) {
                return response()->json(['error' => 'File not found.'], 404);
            }
            $fileSize = $disk->size($filename);
            $mimeType = $this->getMimeType($filename);
            return redirect(
                Storage::disk('s3')->temporaryUrl(
                    $path,
                    now()->addMinutes(300),
                    [
                        'ResponseContentDisposition' => 'inline',
                    ]
                )
            );

            //cloud url

            //temp url
            // $Asset     = $this->assetService->findByAssetKey($assetKey, $request);
            // $asset_url = isset($Asset->asset_url) ? config('deegest.s3_bucket_url') . $Asset->asset_url : "";
            // $disk      = Storage::disk('s3');
            // $path      = $Asset->asset_url;

            // /* stream video start */
            // $filename = $path;
            // if (! $disk->exists($path)) {
            //     return response()->json(['error' => 'File not found.'], 404);
            // }
            // $fileSize = $disk->size($filename);
            // $mimeType = $this->getMimeType($filename);
            // return redirect(
            //     Storage::disk('s3')->temporaryUrl(
            //         $path,
            //         now()->addMinutes(360),
            //         [
            //             'ResponseContentDisposition' => 'inline', // or 'attachment; filename="video.mp4"' for download
            //         ]
            //     )
            // );
        } catch (\Throwable $throwable) {
            Log::error('Asset Upload Validation Error: ' . $throwable->getMessage());
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }
    /**
     * This method is used to view the asset.
     *
     * @param Request $request The request containing the asset_id.
     *
     * @return JsonResponse
     */
    public function assetVideoDownload(string $assetKey, Request $request)
    {
        try {
            $Asset = $this->assetService->findByAssetKey($assetKey, $request);
            if ($request->has('sharelink_id')) {
                $notifyUser = $this->assetService->notifyUserAssetDownload($Asset, $request);
            }
            $asset_url = isset($Asset->asset_url) ? config('deegest.s3_bucket_url') . $Asset->asset_url : "";
            $disk      = Storage::disk('s3');
            $path      = $Asset->asset_url;
            $fileName  = basename($path);
            if (! $disk->exists($path)) {
                abort(404, 'File not found.');
            }

            $fileName = basename($path);
            $mimeType = $disk->mimeType($path);
            return new StreamedResponse(function () use ($disk, $path) {
                $stream = $disk->readStream($path);
                if ($stream === false) {
                    throw new \RuntimeException("Could not open S3 stream for reading.");
                }

                while (!feof($stream)) {
                    echo fread($stream, 1024 * 1024); // read in 1MB chunks
                    ob_flush();
                    flush();
                }

                fclose($stream);
            }, 200, [
                'Content-Type'        => $mimeType,
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                'Cache-Control'       => 'no-cache, no-store, must-revalidate',
                'Pragma'              => 'no-cache',
                'Expires'             => '0',
            ]);

            // dd($disk,$path);
            // $filePath = $path;
            // return redirect(
            //     Storage::disk('s3')->temporaryUrl(
            //         $path,
            //         now()->addMinutes(120),
            //         [
            //             'ResponseContentDisposition' => 'attachment; filename="' . $fileName . '"', // Force download
            //         ]
            //     )
            // );


        } catch (\Throwable $throwable) {
            Log::error('Asset Upload Validation Error: ' . $throwable->getMessage());
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }
    /**
     * This method is used to view the asset as a video.
     *
     * @param string $assetKey The asset key.
     * @param Request $request The request containing the asset_id.
     *
     * @return JsonResponse
     */
    public function assetVideoCloud(string $assetKey, Request $request)
    {
        try {
            $Asset     = $this->assetService->findByAssetKey($assetKey, $request);
            $asset_url = isset($Asset->asset_url) ? config('deegest.s3_bucket_url') . $Asset->asset_url : "";
            $disk      = Storage::disk('s3');
            $path      = $Asset->asset_url;
            $path = ltrim($Asset->asset_url, '/');
            /* stream video start */
            $filename = $path;
            dd($filename);
            if (! $disk->exists($path)) {
                return response()->json(['error' => 'File not found.'], 404);
            }
            $fileSize = $disk->size($filename);
            $mimeType = $this->getMimeType($filename);
            return redirect(
                $this->cloudfrontTemporaryUrl(
                    $path,
                    360 // minutes
                )
            );
        } catch (\Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }
    /**
     * Get a temporary CloudFront URL that can be used to access the asset via CloudFront.
     *
     * @param string $path The path of the asset on S3.
     * @param int $minutes The number of minutes the URL should be valid for.
     *
     * @return string A temporary URL that can be used to access the asset via CloudFront.
     */
    public function cloudfrontTemporaryUrl($path, $minutes)
    {
        $client = new CloudFrontClient([
            'region'  => 'us-east-1',  // CloudFront is global, but AWS SDK needs a region
            'version' => 'latest',
        ]);
        $resourceUrl = rtrim(env('CLOUDFRONT_URL'), '/') . '/' . ltrim($path, '/');
        return $client->getSignedUrl([
            'url'         => $resourceUrl,
            'expires'     => now()->addMinutes($minutes)->timestamp,
            'key_pair_id' => env('CLOUDFRONT_KEY_PAIR_ID'),
            'private_key' => storage_path('keys/cloudfront-private-key.pem'),
        ]);
    }
    private function streamEntireFile($disk, $filename, $fileSize, $mimeType)
    {
        $stream = $disk->readStream($filename);

        return response()->stream(function () use ($stream) {
            while (! feof($stream)) {
                echo fread($stream, 8192); // Read in 8KB chunks
                flush();
            }
            fclose($stream);
        }, 200, [
            'Content-Type'   => $mimeType,
            'Content-Length' => $fileSize,
            'Accept-Ranges'  => 'bytes',
            'Cache-Control'  => 'no-cache',
        ]);
    }

    private function streamRange($disk, $filename, $start, $end, $length, $fileSize, $mimeType)
    {
        // public/assets/20/private portal share.mov
        // Get S3 client for range requests
        //  $s3Client = $disk->getAdapter()->getClient();
        $config   = config('filesystems.disks.s3');
        $s3Client = new S3Client([
            'region'      => env('S3_UPLOADS_REGION'),
            'version'     => 'latest',
            'credentials' => [
                'key'    => env('S3_UPLOADS_KEY'),
                'secret' => env('S3_UPLOADS_SECRET'),
            ],
        ]);
        $bucket = env('S3_UPLOADS_BUCKET');
        //dd($bucket);
        // Use S3 GetObject with Range parameter
        $result = $s3Client->getObject([
            'Bucket' => $bucket,
            'Key'    => $filename,
            'Range'  => "bytes={$start}-{$end}",
        ]);

        $stream = $result['Body'];

        return response()->stream(function () use ($stream) {
            while (! $stream->eof()) {
                echo $stream->read(8192); // Read in 8KB chunks
                flush();
            }
        }, 206, [
            'Content-Type'   => $mimeType,
            'Content-Length' => $length,
            'Content-Range'  => "bytes {$start}-{$end}/{$fileSize}",
            'Accept-Ranges'  => 'bytes',
            'Cache-Control'  => 'no-cache',
        ]);
    }

    private function parseRangeHeader($range, $fileSize)
    {
        $ranges = [];

        if (preg_match('/bytes=(.+)/', $range, $matches)) {
            $rangeSpecs = explode(',', $matches[1]);

            foreach ($rangeSpecs as $rangeSpec) {
                $rangeSpec = trim($rangeSpec);

                if (strpos($rangeSpec, '-') !== false) {
                    list($start, $end) = explode('-', $rangeSpec, 2);

                    $start = $start === '' ? 0 : intval($start);
                    $end   = $end === '' ? $fileSize - 1 : intval($end);

                    // Validate range
                    if ($start >= 0 && $end < $fileSize && $start <= $end) {
                        $ranges[] = ['start' => $start, 'end' => $end];
                    }
                }
            }
        }

        return $ranges;
    }

    private function getMimeType($filename)
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        $mimeTypes = [
            'mp4'  => 'video/mp4',
            'webm' => 'video/webm',
            'ogg'  => 'video/ogg',
            'avi'  => 'video/x-msvideo',
            'mov'  => 'video/quicktime',
            'wmv'  => 'video/x-ms-wmv',
            'flv'  => 'video/x-flv',
            'mkv'  => 'video/x-matroska',

            // For Audio Files
            'mp3'  => 'video/mp3',
            'wav'  => 'video/wav',
            'aac'  => 'video/aac',
            'wma'  => 'video/wma',
            'mid'  => 'video/mid',
            'midi' => 'video/midi',
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }


    /**
     * This method is used to view the asset.
     *
     * @param Request $request The request containing the asset_id.
     *
     * @return JsonResponse
     */
    public function assetThumbViewer(string $assetKey, Request $request)
    {
        try {
            $Asset = $this->assetService->findByAssetKey($assetKey, $request);
            //$asset_url = isset($Asset->asset_url) ? config('deegest.s3_bucket_url') . $Asset->asset_url : "";
            $thumb_url = isset($Asset->url) ? config('deegest.s3_bucket_url') . $Asset->url : "";
            $disk      = Storage::disk('s3');
            $path      = $Asset->url;
            if (! $path) {
                return response()->json([
                    'message' => 'Asset not found or URL is missing.',
                ], 404);
            }
            return redirect(
                Storage::disk('s3')->temporaryUrl(
                    $path,
                    now()->addMinutes(30),
                    [
                        'ResponseContentDisposition' => 'inline', // or 'attachment; filename="video.mp4"' for download
                    ]
                )
            );
            // dd($disk,$path);
            // if (!$disk->exists($path)) {
            //     abort(404, 'File not found.');
            // }

            // $mimeType = $disk->mimeType($path);
            // $fileName = basename($path);

            // return new StreamedResponse(function () use ($disk, $path) {
            //     $stream = $disk->readStream($path);
            //     fpassthru($stream);
            //     fclose($stream);
            // }, 200, [
            //     'Content-Type' => $mimeType,
            //     'Content-Disposition' => "inline; filename=\"{$fileName}\"",
            //     'Cache-Control' => 'no-cache, no-store, must-revalidate',
            // ]);
        } catch (\Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * This method is used to download the asset.
     *
     * @param Request $request The request containing the asset_id.
     *
     * @return JsonResponse
     */
    public function assetDownload(string $assetKey, Request $request)
    {
        try {
            $Asset     = $this->assetService->findByAssetKey($assetKey, $request);
            $asset_url = isset($Asset->asset_url) ? config('deegest.s3_bucket_url') . $Asset->asset_url : "";
            if ($request->has('sharelink_id')) {
                $notifyUser = $this->assetService->notifyUserAssetDownload($Asset, $request);
            }
            // $notifyUser = $this->assetService->notifyUserAssetDownload($Asset);
            $disk = Storage::disk('s3');
            $path = $Asset->asset_url;
            // dd($disk,$path);
            if (! $disk->exists($path)) {
                abort(404, 'File not found.');
            }

            $fileName = basename($path);
            $mimeType = $disk->mimeType($path);
            return new StreamedResponse(function () use ($disk, $path) {
                $stream = $disk->readStream($path);
                if ($stream === false) {
                    throw new \RuntimeException("Could not open S3 stream for reading.");
                }

                while (!feof($stream)) {
                    echo fread($stream, 1024 * 1024); // read in 1MB chunks
                    ob_flush();
                    flush();
                }

                fclose($stream);
            }, 200, [
                'Content-Type'        => $mimeType,
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                'Cache-Control'       => 'no-cache, no-store, must-revalidate',
                'Pragma'              => 'no-cache',
                'Expires'             => '0',
            ]);

            // return new StreamedResponse(function () use ($disk, $path) {
            //     $stream = $disk->readStream($path);
            //     fpassthru($stream);
            //     fclose($stream);
            // }, 200, [
            //     'Content-Type'        => Storage::disk('s3')->mimeType($path),
            //     'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            //     'Cache-Control'       => 'no-cache',
            //     'Pragma'              => 'no-cache',
            // ]);
        } catch (\Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * This method is used to download the asset.
     *
     * @param Request $request The request containing the asset_id.
     *
     * @return JsonResponse
     */
    public function assetThumbDownload(string $assetKey, Request $request)
    {
        try {
            $Asset     = $this->assetService->findByAssetKey($assetKey, $request);
            $thumb_url = isset($Asset->url) ? config('deegest.s3_bucket_url') . $Asset->url : "";
            $disk      = Storage::disk('s3');
            $path      = $Asset->url;
            // dd($disk,$path);
            if (! $disk->exists($path)) {
                abort(404, 'File not found.');
            }

            $fileName = basename($path);

            return new StreamedResponse(function () use ($disk, $path) {
                $stream = $disk->readStream($path);
                fpassthru($stream);
                fclose($stream);
            }, 200, [
                'Content-Type'        => Storage::disk('s3')->mimeType($path),
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                'Cache-Control'       => 'no-cache',
                'Pragma'              => 'no-cache',
            ]);
        } catch (\Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }
    public function assetZipEncode(Request $request)
    {
        try {
            $data    = $request->input('asset_id'); // Example: "Hello World"
            $encoded = $this->base64UrlEncode($data);

            return $this->successResponse(
                $encoded,
                trans(
                    'common.fetch_successfully',
                    ['module' => $this->moduleName]
                )
            );
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * This method is used to download the asset.
     *
     * @param Request $request The request containing the asset_id.
     *
     * @return JsonResponse
     */
    public function assetZipDownload(string $encoded, Request $request)
    {
        try {
            $decoded = $this->base64UrlDecode($encoded);
            $assetIds = explode(',', $decoded);

            // Optional: Track download if via share link
            if ($request->has('sharelink_id')) {
                $this->assetService->notifyUserAssetZipDownload($assetIds, $request);
            }

            $files = $this->assetService->findByAsset($assetIds);

            if (empty($files) || !is_array($files)) {
                return response()->json(['error' => 'Invalid or empty file list'], 422);
            }

            $s3 = Storage::disk('s3');

            // Name of ZIP file
            $zipName = 'asset_' . now()->format('Ymd_His') . '.zip';

            // Create streamed response
            return new StreamedResponse(function () use ($files, $s3, $zipName) {

                // Clean any output buffers before sending ZIP
                while (ob_get_level()) {
                    ob_end_clean();
                }

                // Set ZipStream options
                $options = new Archive();
                $options->setSendHttpHeaders(false); // We'll send headers manually
                $options->setFlushOutput(true); // Flush output to browser as we go

                $zip = new ZipStream($zipName, $options);

                foreach ($files as $filePath) {
                    if ($s3->exists($filePath)) {
                        $stream = $s3->readStream($filePath);
                        if ($stream) {
                            $zip->addFileFromStream(basename($filePath), $stream);
                            fclose($stream);
                        }
                    }
                }

                $zip->finish(); // ✅ Finalize ZIP archive

            }, 200, [
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => "attachment; filename=\"{$zipName}\"",
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Returns all the extensions of the assets
     * @return \Illuminate\Http\JsonResponse
     */
    public function getExtension()
    {
        $extesnion = $this->assetService->getExtension();
        return $this->successResponse(
            $extesnion,
            trans(
                'common.fetch_successfully',
                ['module' => $this->moduleName]
            )
        );
    }

    public function assetAudioViewer(string $assetKey, Request $request)
    {
        try {
            $Asset     = $this->assetService->findByAssetKey($assetKey, $request);
            $asset_url = isset($Asset->asset_url) ? config('deegest.s3_bucket_url') . $Asset->asset_url : "";
            $disk      = Storage::disk('s3');
            $path      = $Asset->asset_url;
            /* stream video start */
            $filename = $path;
            if (! $disk->exists($path)) {
                return response()->json(['error' => 'File not found.'], 404);
            }
            $fileSize = $disk->size($filename);
            $mimeType = $this->getMimeType($filename);

            // Get the range header
            // $range = $request->header('Range');
            $range = 'Range: bytes=0-50';

            if (! $range) {
                // No range requested, stream entire file
                return $this->streamEntireFile($disk, $filename, $fileSize, $mimeType);
            }

            // Parse range header
            $ranges = $this->parseRangeHeader($range, $fileSize);

            if (empty($ranges)) {
                return response('Invalid range', 416)
                    ->header('Content-Range', "bytes */{$fileSize}");
            }

            // Use first range (multi-range not commonly supported for video)
            $start  = $ranges[0]['start'];
            $end    = $ranges[0]['end'];
            $length = $end - $start + 1;
            // dd("kl");
            // Stream the requested range
            //return $this->streamRange($disk, $filename, $start, $end, $length, $fileSize, $mimeType);
            /* stream video end */
            /* as it is a video */
            // // dd($disk,$path);
            // $filePath = $path;
            return redirect(
                Storage::disk('s3')->temporaryUrl(
                    $path,
                    now()->addMinutes(120),
                    [
                        'ResponseContentDisposition' => 'inline', // or 'attachment; filename="video.mp4"' for download
                    ]
                )
            );
        } catch (\Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }
}
