<?php

namespace App\Repositories;

use App\Models\Companies;
use App\Repositories\BaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class TagRepository
 *
 * Repository class for interacting with the `Tags` model.
 *
 * @package App\Repositories
 */
class CompanyRepository extends BaseRepository
{
    /**
     * TagRepository constructor.
     *
     * @param Companies $model The underlying model for the repository.
     */
    public function __construct(Companies $model)
    {
        $this->model = $model;
    }

    /**
     * Retrieve a paginated collection of collections which have a workspace with a given slug.
     *
     * @param Request $request The request object.
     *
     * @return LengthAwarePaginator
     */
    public function companyWithWorkspace($request): LengthAwarePaginator
    {
        $relation           = $request->has('include') ? $request->get('include') : ['users'];
        $perPage            = request()->input('per_page', 10000);
        // $slug               = request()->input('slug');
        $append             = $request->all();
        $append['per_page'] = $perPage ?? 10000;
        $columns            = ['*'];
        $searchColumns      = ['name'];
        $search             = $append['search'] ?? '';

        $allowedSortFields = ['id', 'country_name', 'sheet_name', 'created_at', 'updated_at'];
        $append['sort_by'] = in_array($request->input('sort_by'), $allowedSortFields)
            ? $request->input('sort_by')
            : 'created_at';

        $append['sort_order'] = $append['sort_order'] ?? 'desc';

        // $query = Countries::withCount('ports');

        $query = Companies::when($search, function ($query, $search) use ($searchColumns) {
            $query->where(function ($q) use ($search, $searchColumns) {
                foreach ($searchColumns as $column) {
                    $q->orWhere($column, 'ilike', '%' . $search . '%');
                }
            });
        });
        $data = $query->orderBy('port_name', 'asc')->paginate($append['per_page']);
        return $data;
    }

    public function DirectoryWithWorkspace($request): LengthAwarePaginator
    {
        $searchby = $request->input('searchby');
        $country_id = $request->input('country_id');
        $city_id = $request->input('city_id');

        $perPage = $request->input('per_page', 10000);
        $search = $request->input('search', '');
        //$sortBy = $request->input('sort_by', 'country_name');
        $sortOrder = $request->input('sort_order', 'asc');

        $query = \App\Models\Countries::with(['companies.port']);

        if ($searchby === '1') {
            if ($search) {
                $query->whereHas('companies', function ($q) use ($search) {
                    $q->where('company_name', 'ilike', '%' . $search . '%');
                });
            }
        } else {
            if ($country_id) {
                $query->where('id', $country_id);
            }

            $query->when($city_id || $search, function ($q) use ($city_id, $search) {
                $q->whereHas('companies', function ($companyQuery) use ($city_id, $search) {
                    if ($city_id) {
                        $companyQuery->where('port_id', $city_id);
                    }
                    if ($search) {
                        $companyQuery->where('company_name', 'ilike', '%' . $search . '%');
                    }
                    $companyQuery->orderBy('port_name', 'asc');
                });
            });
        }
      

        

        return $query->paginate($perPage);
    }
        /**
         * Retrieve a paginated collection of countries which have a workspace with a given slug.
         *
         * @param Request $request The request object.
         *
         * @return LengthAwarePaginator
         */

    public function DirectoryWithAlphabet($request): LengthAwarePaginator
    {
        $searchby = $request->input('searchby');
        $country_id = $request->input('country_id');
        $city_id = $request->input('city_id');
        $search = $request->input('search', '');
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);

        $query = Companies::with(['country', 'port']);

        if ($searchby === '1') {
            if ($search) {
                $query->where('company_name', 'ilike', '%' . $search . '%');
            }
        } else {
            if ($country_id) {
                $query->where('country_id', $country_id);
            }
            if ($city_id) {
                $query->where('port_id', $city_id);
            }
            if ($search) {
                $query->where('company_name', 'ilike', '%' . $search . '%');
            }
        }

        $companies = $query->orderByRaw('CASE WHEN company_name ~ \'^[0-9]\' THEN 0 ELSE 1 END, company_name ASC')->get();
       

        $grouped = $companies->groupBy(function ($company) {
            $firstChar = strtoupper(substr($company->company_name, 0, 1));
            return is_numeric($firstChar) ? '0-9' : $firstChar;
        });

        $alphabets = $grouped->keys()->sort()->values();
        $total = $alphabets->count();
        $offset = ($page - 1) * $perPage;
        $paginatedAlphabets = $alphabets->slice($offset, $perPage);
        //dd($paginatedAlphabets,$grouped);
        
        $paginatedData = $paginatedAlphabets->mapWithKeys(function ($letter) use ($grouped) {
            return [$letter => $grouped[$letter]];
        });
       // dd($paginatedData);
        return new LengthAwarePaginator(
            $paginatedData,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'pageName' => 'page']
        );
    }
    public function getPersonsByCompanyId(int $companyId)
    {
        return $this->model->with('persons')->findOrFail($companyId)->persons;
    }
}
