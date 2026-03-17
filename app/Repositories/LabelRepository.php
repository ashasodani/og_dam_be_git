<?php
namespace App\Repositories;

use App\Models\Labels;
use App\Repositories\BaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class LabelRepository
 *
 * Repository class for interacting with the `LABEL` model.
 *
 * @package App\Repositories
 */
class LabelRepository extends BaseRepository
{
    /**
     * LabelRepository constructor.
     *
     * @param label $model The underlying model for the repository.
     */
    public function __construct(Labels $model)
    {
        $this->model = $model;
    }

    /**
     * Get the query collection instance.
     *
     * @param array $columns   The columns to be selected.
     * @param array $relations The relationships to be eager loaded.
     *
     *
     */
    public function getParentLabelsCollection($slug)
    {
        return Labels::with(['workspaces' => function($query) use ($slug) {
            $query->where('slug', $slug);
        }])->whereHas('workspaces', function ($query) use ($slug) {
            $query->where('slug', $slug);
        })->where('parent_key', null)->get();
    }
     /**
     * Retrieve a paginated collection of collections which have a workspace with a given slug.
     *
     * @param Request $request The request object.
     *
     * @return LengthAwarePaginator
     */
    public function LabelWithWorkspace($request): LengthAwarePaginator
    {
        $relation = $request->has('include') ? $request->get('include') : ['users'];
        $perPage = request()->input('per_page', 10000);
        $slug = request()->input('slug');
        $append = $request->all();
        $append['per_page'] = $perPage??10000;
        $columns = ['*'];
        $searchColumns = ['name'];
        $search = $append['search']??'';
        $append['sort_by'] = $append['sort_by']??'created_at';
        $append['sort_order'] = $append['sort_order']??'desc';

        $query = Labels::with(['workspaces' => function($query) use ($slug) {
            $query->where('slug', $slug);
        }])->whereHas('workspaces', function ($query) use ($slug) {
            $query->where('slug', $slug);
        });
        $query->when($search, function ($query, $search) use ($searchColumns) {
            $query->where(function ($q) use ($search, $searchColumns) {
                foreach ($searchColumns as $column) {
                    $q->orWhere($column, 'ILIKE', '%' . $search . '%');
                }
            });
        });
        $data= $query->orderBy($append['sort_by'], $append['sort_order'])->paginate($append['per_page']);
        return $data;
    }
}
