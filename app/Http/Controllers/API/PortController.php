<?php
namespace App\Http\Controllers\API;

use App\Enums\PermissionEnum;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Requests\Country\CountryCreateRequest;
use App\Http\Requests\Country\CountryUpdateRequest;
use App\Http\Resources\BaseCollection;
use App\Http\Resources\CountryResource;
use App\Models\Countries;
use App\Services\CountryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class CountryController extends BaseController
{
    /**
     * @var CountryService The service for handling section operations.
     */
    protected $countryService;

    /**
     * @var permissionSlugs The slug for handling section operations.
     */
    protected $permissionSlugs;

    /**
     * TagController constructor
     *
     * @param CountryService   $tagService   The service for handling Tag related operations.
     */
    public function __construct(CountryService $countryService)
    {
        $this->countryService      = $countryService;
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
        $this->authorize($this->permissionSlugs["countries"]["list"], Countries::class);

        $tagData = $this->countryService->getCountryCollection($request);

        try {
            return $this->successResponse(
                new BaseCollection($tagData, CountryResource::class),
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
    public function store(CountryCreateRequest $request, Countries $model): JsonResponse
    {
        $this->authorize($this->permissionSlugs["countries"]["create"], Countries::class);

        try {
            $country = $this->countryService->createCountry($request);
            return $this->successResponse(
                new CountryResource($country),
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
        $this->authorize($this->permissionSlugs["countries"]["list"], Countries::class);

        try {
            $include = $request->get('include') ? [$request->get('include')] : [];
            $tag     = $this->countryService->findByCountryId($Id, $include);
            return $this->successResponse(
                new CountryResource($tag),
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
     * Remove the specified Tag from storage.
     *
     * @param Request $request The request object.
     * @param int  $tagid id of the tag.
     *
     * @return Mixed
     */
    public function destroy(Request $request, int $countryId): Mixed
    {
        $this->authorize($this->permissionSlugs["countries"]["delete"], Countries::class);

        try {
            $country = $this->countryService->deleteCountryById($countryId);
            if ($country) {
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
    public function getCountries(Request $request): Mixed
    {
        $countryData = $this->countryService->getCountryCollection($request);

        try {
            return $this->successResponse(
                CountryResource::collection($countryData),
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
