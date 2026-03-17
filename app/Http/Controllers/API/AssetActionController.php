<?php

namespace App\Http\Controllers\API;

use App\Enums\PermissionEnum;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Requests\Asset\AssetCopyRequest;
use App\Http\Requests\Asset\AssetDeleteRequest;
use App\Http\Requests\Asset\AssetFolderAssignRequest;
use App\Http\Requests\Asset\AssetLabelAssignRequest;
use App\Http\Requests\Asset\AssetLargeFileUploadRequest;
use App\Http\Requests\Asset\AssetMergeRequest;
use App\Http\Requests\Asset\AssetMoveRequest;
use App\Http\Requests\Asset\AssetShareLinkRequest;
use App\Http\Requests\Asset\AssignCollectionRequest;
use App\Http\Requests\Asset\CommonTagRequest;
use App\Http\Requests\Asset\AssignLabelRequest;
use App\Http\Requests\Asset\AssignTagRequest;
use App\Http\Requests\Asset\RemoveFromCollectionsRequest;
use App\Http\Requests\Collection\CollectionRemoveRequest;
use App\Http\Requests\Asset\RemoveFromLabelsRequest;
use App\Http\Resources\AssetResource;
use App\Http\Resources\AssetLogResource;
use App\Models\ShareLinks;
use App\Services\AssetActionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AssetActionController extends BaseController
{
    /**
     * @var assetActionService The service for handling Asset operations.
     */
    protected $assetActionService;

    /**
     * @var permissionSlugs The slug for handling Asset operations.
     */
    protected $permissionSlugs;

    /**
     * AssetController constructor
     *
     * @param AssetActionService   $assetActionService   The service for handling Asset related operations.
     */
    public function __construct(AssetActionService $assetActionService)
    {
        $this->assetActionService = $assetActionService;
        $this->moduleName         = trans("asset.module_name");
        $this->permissionSlugs    = PermissionEnum::Slugs->getAll();
        //$this->authorizeResource(Assets::class, 'Asset');
    }

    /**
     * Remove the multiple Assets from storage.
     *
     * @param Request $request The request object.
     * @param int  $Asset id of the Asset.
     *
     * @return Mixed
     */
    public function deleteAsset(AssetDeleteRequest $request): Mixed
    {
        try {
            $Asset = $this->assetActionService->bulkAssetDelete($request->all());
            if ($Asset) {
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
     * Move asset to section or folder resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function assetMove(AssetMoveRequest $request): JsonResponse
    {
        try {
            $Asset = $this->assetActionService->moveAssetWithLocations($request->all());
            return $this->successResponse([],
                trans(
                    'common.move_successfully',
                    ['module' => $this->moduleName]
                )
            );
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Copy asset to section or folder resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function assetCopy(AssetCopyRequest $request): JsonResponse
    {
        try {
            $Asset = $this->assetActionService->copyAssetWithLocations($request->all());
            return $this->successResponse(
                new AssetResource($Asset),
                trans(
                    'common.copy_successfully',
                    ['module' => $this->moduleName]
                )
            );
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * merge asset to section or folder resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function assetMerge(AssetMergeRequest $request): JsonResponse
    {
        try {
            $Asset = $this->assetActionService->mergeAssetWithLocations($request->all());
            return $this->successResponse(
                new AssetResource($Asset),
                trans(
                    'common.merge_successfully',
                    ['module' => $this->moduleName]
                )
            );
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Assign label to assets
     *
     * @param AssetLabelAssignRequest $request
     * @return JsonResponse
     */
    public function assignLabel(AssetLargeFileUploadRequest $request): JsonResponse
    {
        try {
            $Asset = $this->assetActionService->assignAssetsWithLabel($request->all());
            return $this->successResponse(
                new AssetResource($Asset),
                trans(
                    'common.assign_successfully',
                    ['module' => $this->moduleName . ' Label']
                )
            );
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Add asset to section or folder resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function assetFolderAssign(AssetFolderAssignRequest $request): JsonResponse
    {
        try {
            $Asset = $this->assetActionService->assignAssetFolder($request->all());
            return $this->successResponse([],
                trans(
                    'common.assign_successfully',
                    ['module' => $this->moduleName]
                )
            );
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Add asset to sharelink or folder resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function assetSharelinkAssign(AssetShareLinkRequest $request): JsonResponse
    {
        try {
            $Asset = $this->assetActionService->assignAssetShareLink($request->all());
            foreach ($request->sharelink_id as $sharelink) {
                $shareLink = ShareLinks::find($sharelink);
                $notifyUser = $this->assetActionService->notifyUserForAssetAdd($shareLink,$request->all());
            }
            
            return $this->successResponse(
                [],
                trans(
                    'common.assign_successfully',
                    ['module' => $this->moduleName]
                )
            );
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Assign Assets to Collections.
     *
     * @param AssignCollectionRequest $request The request containing the asset_id and collection_id
     *
     * @return JsonResponse
     */
    public function assignToCollections(AssignCollectionRequest $request): JsonResponse
    {
        try {
            $data = $request->all();

            $assigned = $this->assetActionService->assignAssetsToCollections(
                $data['asset_id'],
                $data['collection_id'],
                $data['subfolder_id']??[],
            );

            return $this->successResponse(
                $assigned,
                trans('common.assign_successfully', ['module' => $this->moduleName])
            );
        } catch (Throwable $e) {
            report($e);
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Remove assets from all collections.
     *
     * @param RemoveFromCollectionsRequest $request The request containing the asset_id to be removed from collections.
     *
     * @return JsonResponse The JSON response indicating the success or failure of the operation.
     */
    public function removeFromCollections(RemoveFromCollectionsRequest $request): JsonResponse
    {
        try {
            $data = $request->all();

            $detached = $this->assetActionService->removeAssetsFromAllCollections($data['asset_id']);

            return $this->successResponse(
                $detached,
                trans('common.remove_successfully', ['module' => $this->moduleName])
            );
        } catch (Throwable $e) {
            report($e);
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

     /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeAssets(int $Id, CollectionRemoveRequest $request): JsonResponse
    {
        //$this->authorize($this->permissionSlugs["share_links"]["create"], ShareLinks::class);
        try {
            $input     = $request->all();
            $collection = $this->assetActionService->removeCollectionAsset($Id, $input);
           // $notifyUser = $this->assetActionService->notifyUserForAssetRemove($shareLink);
            return $this->successResponse(
                $input,
                trans(
                    'common.remove_successfully',
                    ['module' => $this->moduleName]
                )
            );
        } catch (\Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Assign Assets to Labels.
     *
     * @param AssignLabelRequest $request The request containing the asset_id and label_id
     *
     * @return JsonResponse The JSON response indicating the success or failure of the operation.
     */
    public function assignToLabels(AssignLabelRequest $request): JsonResponse
    {
        try {
            $data = $request->all();

            $assigned = $this->assetActionService->assignAssetsToLabels(
                $data['asset_id'],
                $data['label_id']
            );

            return $this->successResponse(
                $assigned,
                trans('common.assign_successfully', ['module' => $this->moduleName])
            );
        } catch (Throwable $e) {
            report($e);
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Assign Assets to Labels.
     *
     * @param AssignTagRequest $request The request containing the asset_id and label_id
     *
     * @return JsonResponse The JSON response indicating the success or failure of the operation.
     */

    public function assignToTags(AssignTagRequest $request): JsonResponse
    {
        try {
            $assetsData = $request->input('assets');
            $result     = $this->assetActionService->assignAssetsToTags($assetsData);

            return $this->successResponse(
                $result,
                trans('common.assign_successfully', ['module' => $this->moduleName])
            );
        } catch (Throwable $e) {
            report($e);
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Remove assets from all labels.
     *
     * @param RemoveFromLabelsRequest $request The request containing the asset_id to be removed from labels.
     *
     * @return JsonResponse The JSON response indicating the success or failure of the operation.
     */
    public function removeFromLabels(RemoveFromLabelsRequest $request): JsonResponse
    {
        try {
            $data = $request->all();

            $detached = $this->assetActionService->removeAssetsFromAllLabels($data['asset_id']);

            return $this->successResponse(
                $detached,
                trans('common.remove_successfully', ['module' => $this->moduleName])
            );
        } catch (Throwable $e) {
            report($e);
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Retrieve the update log for an asset.
     *
     * @param int $AssetId ID of the asset to retrieve the update log for.
     * @param Request $request The request containing the parameters to filter the update log.
     *
     * @return JsonResponse The JSON response containing the update log for the asset.
     */
    public function assetUpdateLog(int $AssetId, Request $request): JsonResponse
    {
        try {
            $Asset = $this->assetActionService->getUpdateLog($AssetId);
            return $this->successResponse(
                AssetLogResource::collection($Asset),
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

    
    public function commonTags(CommonTagRequest $request): JsonResponse
    {
        try {
            $assetIds = $request->input('asset_id');
            $result     = $this->assetActionService->getCommonTags($assetIds);

            return $this->successResponse(
                $result,
                trans('common.fetch_successfully', ['module' => $this->moduleName])
            );
        } catch (Throwable $e) {
            report($e);
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
