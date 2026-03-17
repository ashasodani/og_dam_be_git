<?php
namespace App\Http\Controllers\API;

use App\Enums\PermissionEnum;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Requests\Tag\TagCreateRequest;
use App\Http\Requests\Tag\TagUpdateRequest;
use App\Http\Resources\BaseCollection;
use App\Http\Resources\TagResource;
use App\Models\Tags;
use App\Services\TagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class TagController extends BaseController
{
    /**
     * @var TagService The service for handling section operations.
     */
    protected $tagService;

    /**
     * @var permissionSlugs The slug for handling section operations.
     */
    protected $permissionSlugs;

    /**
     * TagController constructor
     *
     * @param TagService   $tagService   The service for handling Tag related operations.
     */
    public function __construct(TagService $tagService)
    {
        $this->tagService      = $tagService;
        $this->moduleName      = trans("tag.module_name");
        $this->permissionSlugs = PermissionEnum::Slugs->getAll();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Mixed
     */
    public function index(Request $request): mixed
    {
        $this->authorize($this->permissionSlugs["tags"]["list"], Tags::class);

        $tagData = $this->tagService->getTagCollection($request);

        try {
            return $this->successResponse(
                new BaseCollection($tagData, TagResource::class),
                trans(
                    'common.fetch_successfully',
                    ['module' => $this->moduleName]
                ));

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
    public function store(TagCreateRequest $request, Tags $model): JsonResponse
    {
        $this->authorize($this->permissionSlugs["tags"]["create"], Tags::class);

        try {
            $tag = $this->tagService->createTag($request);
            return $this->successResponse(
                new TagResource($tag),
                trans(
                    'common.create_successfully',
                    ['module' => $this->moduleName]
                ));
        } catch (\Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Display the specified Tag.
     *
     * @param int $tagId The ID of the Tag to view.
     *
     * @return Mixed
     */
    public function show(int $Id, Request $request): mixed
    {
        $this->authorize($this->permissionSlugs["tags"]["list"], Tags::class);

        try {
            $include = $request->get('include') ? [$request->get('include')] : [];
            $tag     = $this->tagService->findByTagId($Id, $include);
            return $this->successResponse(
                new TagResource($tag),
                trans(
                    'common.fetch_successfully',
                    ['module' => $this->moduleName]
                ));
        } catch (\Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Update the Tag in storage.
     *
     * @param int  $id Uuid of the tag.
     * @param TagUpdateRequest $request The request containing the validated data for updating tag.
     *
     * @return JsonResponse
     */
    public function update(int $tagId, TagUpdateRequest $tagRequest): JsonResponse
    {
        $this->authorize($this->permissionSlugs["tags"]["update"], Tags::class);

        try {
            $data = $tagRequest->validated();
            $tag  = $this->tagService->updateTag($tagId, $data);

            return $this->successResponse(
                new TagResource($tag),
                trans(
                    'common.update_successfully',
                    ['module' => $this->moduleName]
                ));
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Remove the specified Tag from storage.
     *
     * @param Request $request The request object.
     * @param int  $tagid id of the tag.
     *
     * @return Mixed
     */
    public function destroy(Request $request, int $tagId): Mixed
    {
        $this->authorize($this->permissionSlugs["tags"]["delete"], Tags::class);

        try {
            $tag = $this->tagService->deleteTagById($tagId);
            if ($tag) {
                return $this->successResponse([],
                    trans(
                        'common.delete_successfully',
                        ['module' => $this->moduleName]
                    ));
            }
            return $this->sendError('Something Wrong.', trans('common.something_wrong'), 401);
        } catch (\Throwable $throwable) {
            report($throwable);
            return $this->sendError('error', $throwable->getMessage(), 500);
        }
    }
     /**
     * Get all tag the specified Tag from storage.
     *
     * @param Request $request The request object.
     * @param int  $tagid id of the tag.
     *
     * @return Mixed
     */
    public function getTag(Request $request): Mixed
    {
        $tagData = $this->tagService->getTagCollection($request);

        try {
            return $this->successResponse(
                TagResource::collection($tagData),
                trans(
                    'common.fetch_successfully',
                    ['module' => $this->moduleName]
                ));
           
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }
}
