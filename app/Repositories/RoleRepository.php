<?php

namespace App\Repositories;

use App\Models\Role;
use App\Repositories\BaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class RoleRepository
 *
 * This class represents the repository for handling database operations related to roles.
 *
 * @package App\Repositories
 */
class RoleRepository extends BaseRepository
{
    /**
     * RoleRepository constructor.
     *
     * @param Role $roleModel The model associated with this repository.
     */
    public function __construct(Role $roleModel)
    {
        $this->model = $roleModel;
    }
     /**
     * Retrieve a paginated collection of collections which have a workspace with a given slug.
     *
     * @param Request $request The request object.
     *
     * @return LengthAwarePaginator
     */
    public function getRoleData($request): LengthAwarePaginator
    {
        $relation = $request->has('include') ? $request->get('include') : ['users'];
        $perPage = request()->input('per_page');
        $slug = request()->input('slug');
        $append = $request->all();
        $append['per_page'] = $perPage??100;
        $columns = ['*'];
        $searchColumns = ['name', 'default_asset_type'];
        $search = $append['search']??'';
        $append['sort_by'] = $append['sort_by']??'created_at';
        $append['sort_order'] = $append['sort_order']??'desc';

      
        $query = Role::when($search, function ($query, $search) use ($searchColumns) {
            $query->where(function ($q) use ($search, $searchColumns) {
                foreach ($searchColumns as $column) {
                    $q->orWhere($column, 'ILIKE', '%' . $search . '%');
                }
            });
        });
        $data= $query->where('name', '!=', 'Super Admin')->orderBy($append['sort_by'], $append['sort_order'])->paginate($append['per_page']);
        return $data;
    }
}
