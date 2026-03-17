<?php
namespace App\Repositories;

use App\Models\Countries;
use App\Repositories\BaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class TagRepository
 *
 * Repository class for interacting with the `Tags` model.
 *
 * @package App\Repositories
 */
class PortRepository extends BaseRepository
{
    /**
     * TagRepository constructor.
     *
     * @param Countries $model The underlying model for the repository.
     */
    public function __construct(Countries $model)
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
    public function countryWithWorkspace($request): LengthAwarePaginator
    {
        $relation           = $request->has('include') ? $request->get('include') : ['users'];
        $perPage            = request()->input('per_page', 10000);
       // $slug               = request()->input('slug');
        $append             = $request->all();
        $append['per_page'] = $perPage ?? 10000;
        $columns            = ['*'];
        $searchColumns      = ['name'];
        $search             = $append['search'] ?? '';

        $allowedSortFields = ['id', 'country_name','sheet_name', 'created_at', 'updated_at'];
        $append['sort_by'] = in_array($request->input('sort_by'), $allowedSortFields)
        ? $request->input('sort_by')
        : 'created_at';

        $append['sort_order'] = $append['sort_order'] ?? 'desc';

       // $query = Countries::withCount('ports');

        $query = Countries::when($search, function ($query, $search) use ($searchColumns) {
            $query->where(function ($q) use ($search, $searchColumns) {
                foreach ($searchColumns as $column) {
                    $q->orWhere($column, 'ilike', '%' . $search . '%');
                }
            });
        });
        $data = $query->orderBy($append['sort_by'], $append['sort_order'])->paginate($append['per_page']);
        return $data;
    }
}
