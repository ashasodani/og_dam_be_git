<?php
namespace App\Services;

use App\Repositories\CountryRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class CountryService
 * Service class for managing CRUD operations of country
 * @package App\Services
 */
class CountryService
{
    /**
     * @var CountryRepository Repository for interacting with the country data
     */
    protected $countryRepository;

    /**
     * CountryService constructor.
     * @param CountryRepository $countryRepository The repository for interacting with country data.
     */
    public function __construct(CountryRepository $countryRepository)
    {
        $this->countryRepository = $countryRepository;
    }

    /**
     * Create a new Country.
     * @param array $countryData The data for creating the country.
     * @return Model The created country data.
     */
    public function createCountry($request): Model
    {
        $createdCountry = [];
        

        foreach ($request['country_name'] as $record) {
            $countryData       = ['country_name' => $record,'sheet_name' => strtoupper($record).'-D','uuid' => '123hsvc7261786187'];
            $country           = $this->countryRepository->create($countryData);
            $createdCountry[] = $country;
        }

        return end($createdCountry);
    }

    /**
     * Get the query builder for country.
     * @return Builder
     */
    public function getCountryQuery(): Builder
    {
        return $this->countryRepository->query();
    }

    /**
     * Get the query Collection for country.
     * @return LengthAwarePaginator
     */
    public function getCountryCollection($request): LengthAwarePaginator
    {
        return $this->countryRepository->countryWithWorkspace($request);
    }

    /**
     * Get the query Collection for city.
     * @return LengthAwarePaginator
     */
    public function getCityCollection($id): LengthAwarePaginator
    {
        return $this->countryRepository->cityWithWorkspace($id);
    }

    /**
     * Find a country by its ID.
     * @param int $Id The ID of the country.
     * @param array $include
     * @return Model|null The country model or null if not found.
     */
    public function findByCountryId(int $Id, array $include): ?Model
    {
        return $this->countryRepository->findById($Id, ['*'], $include);
    }

    /**
     * Update an existing country.
     * @param int $countryId The ID of the country to be updated.
     * @param array  $countryData The data for updating the country.
     * @return Model
     */

    public function updateCountry(int $countryId, array $countryData): Model
    {
        $country = $this->countryRepository->update($countryId, [
            'country_name' => $countryData['country_name'] ?? null,
            'sheet_name'   => isset($countryData['country_name']) ? strtoupper($countryData['country_name']).'-D' : null,
        ]);

        return $country;
    }

    /**
     * Deleting an existing country.
     * @param int $countryId The id of the country to be deleted.
     * @return bool True on successful deletion, false otherwise.
     */
    public function deleteCountryById(int $countryId): bool
    {
        return $this->countryRepository->deleteById($countryId);
    }
}

