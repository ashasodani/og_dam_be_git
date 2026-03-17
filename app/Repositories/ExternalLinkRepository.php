<?php
namespace App\Repositories;

use App\Models\AddtionalLinks;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class ExternalLinkRepository
 *
 * Repository class for interacting with the `Tiles` model.
 *
 * @package App\Repositories
 */
class ExternalLinkRepository extends BaseRepository
{
    /**
     * ExternalLinkRepository constructor.
     *
     * @param AddtionalLinks $model The underlying model for the repository.
     */
    public function __construct(AddtionalLinks $model)
    {
        $this->model = $model;
    }

    /**
     * Retrieve a paginated collection of collections which have a workspace with a given slug.
     *
     * @param Request $request The request object.
     *
     * @return Collection
     */
    public function linksWithPortals($request): Collection
    {
        $relation           = $request->has('include') ? $request->get('include') : ['relatedPortal'];
        $perPage            = request()->input('per_page');
        $slug               = request()->input('slug');
        $append             = $request->all();
        $append['per_page'] = $perPage ?? 100;
        $columns            = ['*'];
        $searchColumns      = ['name', 'default_asset_type'];
        $search             = $append['search'] ?? '';
         $append['sort_by'] = $append['sort_by']??'created_at';
        $append['sort_order'] = $append['sort_order']??'desc';

        $query = AddtionalLinks::whereHas('portals', function ($query) use ($slug) {
            $query->where('slug', $slug);
        });
        $query->when($search, function ($query, $search) use ($searchColumns) {
            $query->where(function ($q) use ($search, $searchColumns) {
                foreach ($searchColumns as $column) {
                    $q->orWhere($column, 'ILIKE', '%' . $search . '%');
                }
            });
        });

        // Sort by position ASC
        return $query->orderBy('position', 'asc')->get();
    }
}
