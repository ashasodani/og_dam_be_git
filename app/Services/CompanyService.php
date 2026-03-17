<?php
namespace App\Services;

use App\Repositories\CompanyRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\UsersImport;
use App\Models\Countries;

/**
 * Class companyService
 * Service class for managing CRUD operations of company
 * @package App\Services
 */
class CompanyService
{
    /**
     * @var CompanyRepository Repository for interacting with the company data
     */
    protected $companyRepository;

    /**
     * companyService constructor.
     * @param CompanyRepository $countryRepository The repository for interacting with company data.
     */
    public function __construct(CompanyRepository $companyRepository)
    {
        $this->companyRepository = $companyRepository;
    }

  

    public function createCompany($request)
    {
        $createdCountry = [];
        $countries = $request->get('country_id');
        $file = $request->file('import_file');

        foreach ($countries as $country) {
            $sheetName = Countries::where('id', $country)->value('sheet_name');
            if ($sheetName) {
                $import = new UsersImport($sheetName);
                Excel::import($import, $file, null, \Maatwebsite\Excel\Excel::XLSX);
            }
        }
        

            // $company           = $this->countryRepository->create($countryData);
            // $createdCountry[] = $company;

            // Attach asset if provided
            // if (! empty($request['asset_id'])) {
            //     $country->assets()->attach($request['asset_id']);
            // }

            // // Attach single or multiple workspace_ids if provided
            // if (! empty($request['workspace_id'])) {
            //     $workspaceIds = is_array($request['workspace_id']) ? $request['workspace_id'] : [$request['workspace_id']];
            //     $company->workspaces()->attach($workspaceIds);
            // }
        //}

        //return end($createdCountry);
        return $sheetName;
    }

    /**
     * Get the query builder for company.
     * @return Builder
     */
    public function getCountryQuery(): Builder
    {
        return $this->companyRepository->query();
    }

    /**
     * Get the query Collection for company.
     * @return LengthAwarePaginator
     */
    public function getCompanyCollection($request): LengthAwarePaginator
    {
        return $this->companyRepository->companyWithWorkspace($request);
    }

    /**
     * Get countries with their companies for directory listing.
     * @return LengthAwarePaginator
     */
    public function getDirectoryCollection($request): LengthAwarePaginator
    {
        
        return $this->companyRepository->DirectoryWithWorkspace($request);
    }

    public function getDirectoryAlphaCollection($request): LengthAwarePaginator
    {
        
        return $this->companyRepository->DirectoryWithAlphabet($request);
    }

    /**
     * Find an company by their UUID.
     * @param int $companyUuid The UUID of the company.
     * @return Model|null The company model or null if not found.
     */
    public function findByCompanyId(int $Id, array $include): ?Model
    {
        return $this->companyRepository->findById($Id, ['*'], $include);
    }

    /**
     * Update an existing company.
     * @param int $companyId The UUID of the company to be updated.
     * @param array  $companyId The data for updating the company.
     * @return model True on successful update, false otherwise.
     */

    public function updateCompany(int $companyId, array $companyData): Model
    {
        // Update company basic fields
        $country = $this->companyRepository->update($companyId, ['name' => $companyData['name'] ?? null]);

        // Sync asset(s) if provided
        // if (! empty($countryData['asset_id'])) {
        //     $assetIds = is_array($countryData['asset_id']) ? $countryData['asset_id'] : [$countryData['asset_id']];
        //     $company->assets()->sync($assetIds);
        // }

        // Sync workspace(s) if provided
        // if (! empty($companyData['workspace_id'])) {
        //     $workspaceIds = is_array($companyData['workspace_id']) ? $companyData['workspace_id'] : [$companyData['workspace_id']];
        //     $company->workspaces()->sync($workspaceIds);
        // }

        return $country;
    }

    /**
     * Deleting an existing company.
     * @param int $countryId The id of the company to be deleted.
     * @return bool True on successful deletion, false otherwise.
     */
    public function deleteCompanyById(int $companyId): bool
    {
        return $this->companyRepository->deleteById($companyId);
    }
    public function getPersonsByCompanyId(int $companyId)
    {
        return $this->companyRepository->getPersonsByCompanyId($companyId);
    }
}
