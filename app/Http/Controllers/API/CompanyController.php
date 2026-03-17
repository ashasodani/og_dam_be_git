<?php
namespace App\Http\Controllers\API;

use App\Enums\PermissionEnum;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Requests\Company\CompanyCreateRequest;
use App\Http\Requests\Company\CompanyUpdateRequest;
use App\Http\Resources\BaseCollection;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\DirectoryResource;
use App\Http\Resources\DirectoryAlphabaticResource;

use App\Http\Resources\PersonResource;
use App\Models\Companies;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class CompanyController extends BaseController
{
    /**
     * @var CompanyService The service for handling section operations.
     */
    protected $CompanyService;

    /**
     * @var permissionSlugs The slug for handling section operations.
     */
    protected $permissionSlugs;

    /**
     * companyController constructor
     *
     * @param CompanyService   $companyService   The service for handling company related operations.
     */
    public function __construct(CompanyService $CompanyService)
    {
        $this->CompanyService      = $CompanyService;
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
        //$this->authorize($this->permissionSlugs["countries"]["list"], Countries::class);

        $companyData = $this->CompanyService->getCompanyCollection($request);

        try {
            return $this->successResponse(
                new BaseCollection($companyData, CompanyResource::class),
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
    public function store(CompanyCreateRequest $request, Companies $model): JsonResponse
    {
        //$this->authorize($this->permissionSlugs["countries"]["create"], Countries::class);
        
        try {
            $company = $this->CompanyService->createCompany($request);
            return $this->successResponse([],
                trans(
                    'common.create_successfully',
                    ['module' => $this->moduleName]
                ));
        } catch (\Throwable $throwable) {
            dd($throwable);
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Display the specified company.
     *
     * @param int $companyId The ID of the company to view.
     *
     * @return Mixed
     */
    public function show(int $Id, Request $request): mixed
    {
        //$this->authorize($this->permissionSlugs["countries"]["list"], Companies::class);

        try {
            $include = $request->get('include') ? [$request->get('include')] : [];
            $company     = $this->CompanyService->findByCompanyId($Id, $include);
            return $this->successResponse(
                new CompanyResource($company),
                trans(
                    'common.fetch_successfully',
                    ['module' => $this->moduleName]
                ));
        } catch (\Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

     public function getDirectory(Request $request): mixed
    {
        //$this->authorize($this->permissionSlugs["countries"]["list"], Countries::class);

       
       // dd($companyData);
        try {
            if($request->get('order_by') === 'country-city'){
                $companyData = $this->CompanyService->getDirectoryCollection($request);
                return $this->successResponse(
                    new BaseCollection($companyData, DirectoryResource::class),
                    trans(
                        'common.fetch_successfully',
                        ['module' => $this->moduleName]
                    ));
            }else{
                $companyData = $this->CompanyService->getDirectoryAlphaCollection($request);
               // dd($companyData);
                return $this->successResponse(
                    new BaseCollection($companyData, DirectoryAlphabaticResource::class),
                    trans(
                        'common.fetch_successfully',
                        ['module' => $this->moduleName]
                    )
                   );
            }

        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Display a listing of the persons for the specified company.
     *
     * @param int $companyId The ID of the company to view persons for.
     *
     * @return Mixed
     */
    public function getPersons(int $companyId): mixed
    {
        try {
            $persons = $this->CompanyService->getPersonsByCompanyId($companyId);
            return $this->successResponse(
                PersonResource::collection($persons),
                trans(
                    'common.fetch_successfully',
                    ['module' => 'Person']
                ));
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }


    

    /**
     * Remove the specified company from storage.
     *
     * @param Request $request The request object.
     * @param int  $companyid id of the company.
     *
     * @return Mixed
     */
    // public function destroy(Request $request, int $countryId): Mixed
    // {
    //     $this->authorize($this->permissionSlugs["countries"]["delete"], Countries::class);

    //     try {
    //         $country = $this->CompanyService->deleteCountryById($countryId);
    //         if ($country) {
    //             return $this->successResponse([],
    //                 trans(
    //                     'common.delete_successfully',
    //                     ['module' => $this->moduleName]
    //                 ));
    //         }
    //         return $this->sendError('Something Wrong.', trans('common.something_wrong'), 401);
    //     } catch (\Throwable $throwable) {
    //         report($throwable);
    //         return $this->sendError('error', $throwable->getMessage(), 500);
    //     }
    // }
    //  /**
    //  * Get all company the specified company from storage.
    //  *
    //  * @param Request $request The request object.
    //  * @param int  $companyid id of the company.
    //  *
    //  * @return Mixed
    //  */
    // public function getCountries(Request $request): Mixed
    // {
    //     $countryData = $this->CompanyService->getCountryCollection($request);

    //     try {
    //         return $this->successResponse(
    //             CountryResource::collection($countryData),
    //             trans(
    //                 'common.fetch_successfully',
    //                 ['module' => $this->moduleName]
    //             ));
           
    //     } catch (Throwable $throwable) {
    //         report($throwable);
    //         return response()->json(['error' => $throwable->getMessage()], 500);
    //     }
    // }
}
