<?php
namespace App\Http\Controllers\API;

use App\Enums\PermissionEnum;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Requests\Label\LabelCreateRequest;
use App\Http\Requests\Label\LabelUpdateRequest;
use App\Http\Resources\BaseCollection;
use App\Http\Resources\LabelResource;
use App\Models\Labels;
use App\Services\LabelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;use Throwable;

class LabelController extends BaseController
{
    /**
     * @var LabelService The service for handling section operations..
     */
    protected $labelService;

    /**
     * @var permissionSlugs The slug for handling section operations.
     */
    protected $permissionSlugs;

    /**
     * LabelController constructor
     *
     * @param LabelService   $labelService   The service for handling Label related operations.
     */
    public function __construct(LabelService $labelService)
    {
        $this->labelService    = $labelService;
        $this->moduleName      = trans("label.module_name");
        $this->permissionSlugs = PermissionEnum::Slugs->getAll();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Mixed
     */
    public function index(Request $request): mixed
    {
        $this->authorize($this->permissionSlugs["labels"]["list"], Labels::class);

        $labelData = $this->labelService->getLabelsCollection($request);

        try {
            return $this->successResponse(
                new BaseCollection($labelData, LabelResource::class),
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
    public function store(LabelCreateRequest $request): JsonResponse
    {
        $this->authorize($this->permissionSlugs["labels"]["create"], Labels::class);

        try {
            $input = $request->all();
            $label = $this->labelService->createLabel($input);
            return $this->successResponse(
                new LabelResource($label),
                trans(
                    'common.create_successfully',
                    ['module' => $this->moduleName]
                ));
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Display the specified Label.
     *
     * @param int $labelId The ID of the Label to view.
     *
     * @return Mixed
     */
    public function show(int $Id, Request $request): mixed
    {
        $this->authorize($this->permissionSlugs["labels"]["list"], Labels::class);

        try {
            $include = $request->get('include') ? [$request->get('include')] : [];
            $label   = $this->labelService->findByLabelId($Id, $include);
            return $this->successResponse(
                new LabelResource($label),
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
     * Update the Label in storage.
     *
     * @param int  $id Uuid of the label.
     * @param LabelUpdateRequest $request The request containing the validated data for updating label.
     *
     * @return JsonResponse
     */
    public function update(int $labelId, LabelUpdateRequest $labelRequest): JsonResponse
    {
        $this->authorize($this->permissionSlugs["labels"]["update"], Labels::class);

        try {
            $data  = $labelRequest->validated();
            $label = $this->labelService->updateLabel($labelId, $data);

            return $this->successResponse(
                new LabelResource($label),
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
     * Remove the specified Label from storage.
     *
     * @param int  $labelId id of the label.
     *
     * @return Mixed
     */
    public function destroy($labelId): Mixed
    {
        $this->authorize($this->permissionSlugs["labels"]["delete"], Labels::class);

        try {
            $result = $this->labelService->deleteLabelById($labelId);

            if ($result === true) {
                return $this->successResponse([], trans('common.delete_successfully', ['module' => $this->moduleName]));
            } elseif (is_string($result)) {
                return $this->sendError($result, $result, 422); // Validation error
            }

            return $this->sendError('Something went wrong.', trans('common.something_wrong'), 500);
        } catch (\Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Mixed
     */
    public function getParentLabels(Request $request): mixed
    {
        $labelData = $this->labelService->getParentLabelsCollection($request);
        try {
            return $this->successResponse(
                LabelResource::collection($labelData),
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
     * Get all label the specified Tag from storage.
     *
     * @param Request $request The request object.
     * @param int  $tagid id of the tag.
     *
     * @return Mixed
     */
    public function getLabel(Request $request): Mixed
    {
        $labelData = $this->labelService->getLabelsCollection($request);
        try {
            return $this->successResponse(
                LabelResource::collection($labelData),
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
