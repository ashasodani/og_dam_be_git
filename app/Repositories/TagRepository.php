<?php
namespace App\Repositories;

use App\Models\Tags;
use App\Repositories\BaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class TagRepository
 *
 * Repository class for interacting with the `Tags` model.
 *
 * @package App\Repositories
 */
class TagRepository extends BaseRepository
{
    /**
     * TagRepository constructor.
     *
     * @param Tags $model The underlying model for the repository.
     */
    public function __construct(Tags $model)
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
    public function tagWithWorkspace($request): LengthAwarePaginator
    {
        $relation           = $request->has('include') ? $request->get('include') : ['users'];
        $perPage            = request()->input('per_page', 10000);
        $slug               = request()->input('slug');
        $append             = $request->all();
        $append['per_page'] = $perPage ?? 10000;
        $columns            = ['*'];
        $searchColumns      = ['name'];
        $search             = $append['search'] ?? '';

        $allowedSortFields = ['id', 'name', 'created_at', 'updated_at', 'assets_count'];
        $append['sort_by'] = in_array($request->input('sort_by'), $allowedSortFields)
        ? $request->input('sort_by')
        : 'created_at';

        $append['sort_order'] = $append['sort_order'] ?? 'desc';

        $query = Tags::withCount('assets')
            ->whereHas('workspaces', function ($query) use ($slug) {
                $query->where('slug', $slug);
            });

        $query->when($search, function ($query, $search) use ($searchColumns) {
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
