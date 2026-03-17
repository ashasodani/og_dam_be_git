<?php

namespace App\Http\Controllers\API;

use App\Enums\PermissionEnum;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Requests\Collection\CollectionCreateRequest;
use App\Http\Requests\Collection\CollectionUpdateRequest;
use App\Http\Resources\BaseCollection;
use App\Http\Resources\CollectionResource;
use App\Http\Resources\CollectionAssetResource;
use App\Models\Collections;
use App\Models\User;
use App\Services\CollectionService;
use App\Services\ShareLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;


class CollectionController extends BaseController
{
    /**
     * @var CollectionService The service for handling section operations.
     */
    protected $collectionService;

    /**
     * @var sharelinkService The service for handling section operations.
     */
    protected $sharelinkService;

    /**
     * @var permissionSlugs The slug for handling section operations.
     */
    protected $permissionSlugs;

    /**
     * CollectionController constructor
     *
     * @param CollectionService   $collectionService   The service for handling Collection related operations.
     */
    public function __construct(CollectionService $collectionService, ShareLinkService $sharelinkService)
    {
        $this->collectionService = $collectionService;
        $this->sharelinkService = $sharelinkService;
        $this->moduleName        = trans("collection.module_name");
        $this->permissionSlugs   = PermissionEnum::Slugs->getAll();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Mixed
     */
    public function index(Request $request): mixed
    {
        $this->authorize($this->permissionSlugs["collections"]["list"], Collections::class);

        $collectionData = $this->collectionService->getCollectionsCollection($request);
        try {
            return $this->successResponse(
                new BaseCollection($collectionData, CollectionResource::class),
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
    public function store(CollectionCreateRequest $request): JsonResponse
    {
        $this->authorize($this->permissionSlugs["collections"]["create"], Collections::class);

        try {
            $input      = $request->all();
            $collection = $this->collectionService->createCollection($input);
            return $this->successResponse(
                new CollectionResource($collection),
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
     * Display the specified Collection.
     *
     * @param int $collectionId The ID of the Collection to view.
     *
     * @return Mixed
     */
    public function show(int $Id, Request $request): mixed
    {
        // $this->authorize($this->permissionSlugs["collections"]["list"], Collections::class);

        try {
            $include    = $request->get('include') ? [$request->get('include')] : [];
            $collection = $this->collectionService->findByCollectionId($Id, $include);

            return $this->successResponse(
                new CollectionResource($collection),
                trans('common.fetch_successfully', ['module' => $this->moduleName])
            );
        } catch (\Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Update the Collection in storage.
     *
     * @param int  $id Uuid of the collection.
     * @param CollectionUpdateRequest $request The request containing the validated data for updating collection.
     *
     * @return JsonResponse
     */
    public function update(int $collectionId, CollectionUpdateRequest $collectionRequest): JsonResponse
    {
        $this->authorize($this->permissionSlugs["collections"]["update"], Collections::class);

        try {
            $data       = $collectionRequest->validated();
            $collection = $this->collectionService->updateCollection($collectionId, $data);

            /*send the notification to user when update collection start
            foreach ($collection->workspaces as $workspace) {

                $users = $workspace->users_workspaces;

                foreach ($users as $user) {

                $user->notify(new CollectionUpdatedAppNotification($collection));
                }
            }*/

            return $this->successResponse(
                new CollectionResource($collection),
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
     * Remove the specified Collection from storage.
     *
     * @param Request $request The request object.
     * @param int  $collectionId id of the collection.
     *
     * @return Mixed
     */
    public function destroy($collectionId): Mixed
    {
        $this->authorize($this->permissionSlugs["collections"]["delete"], Collections::class);

        try {
            $collection = $this->collectionService->deleteCollectionById($collectionId);
            if ($collection) {
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
     * Display the specified Collection.
     *
     * @param int $collectionId The ID of the Collection to view.
     *
     * @return Mixed
     */
    public function getCollectionAssets(string $slug, Request $request): mixed
    {
        // $this->authorize($this->permissionSlugs["collections"]["list"], Collections::class);

        try {
            $include    = $request->get('include') ? [$request->get('include')] : [];
            $collection = $this->collectionService->findByCollectionSlug($slug, $include);
            if(!$collection){
                return $this->sendError('Collection not found.',  trans(
                    'common.link_not_found',
                    ['module' => $this->moduleName]
                ), 404);
            }
            $notifyUser = $this->collectionService->notifiyUserCollectionView($collection,$request);
            $filteredSections = $this->collectionService->getSelectedData($slug, $request);
            $collection->selectedData = $filteredSections; 
            $collection->filter_section_count = $filteredSections->count();
            return $this->successResponse(
                new CollectionAssetResource($collection),
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
     * Provides suggestions for the current user based on the search query.
     *
     * @param Request $request The request object.
     *
     * @return JsonResponse
     */
    public function suggestions(Request $request)
    {
         try {
            $suggestions = $this->collectionService->getTheSuggestion($request);

            return $this->successResponse($suggestions,
                trans('common.fetch_successfully', ['module' => $this->moduleName])
            );
        } catch (Throwable $e) {
            report($e);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
