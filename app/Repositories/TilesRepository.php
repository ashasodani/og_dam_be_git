<?php
namespace App\Repositories;

use App\Models\Tiles;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class TilesRepository
 *
 * Repository class for interacting with the `Tiles` model.
 *
 * @package App\Repositories
 */
class TilesRepository extends BaseRepository
{
    /**
     * TilesRepository constructor.
     *
     * @param Tiles $model The underlying model for the repository.
     */
    public function __construct(Tiles $model)
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
    public function tilesWithPortals($request): Collection
    {
        $relation           = $request->has('include') ? $request->get('include') : ['users'];
        $perPage            = request()->input('per_page');
        $slug               = request()->input('slug');
        $append             = $request->all();
        $append['per_page'] = $perPage ?? 100;
        $columns            = ['*'];
        $searchColumns      = ['name', 'default_asset_type'];
        $search             = $append['search'] ?? '';

        $query = Tiles::whereHas('portals', function ($query) use ($slug) {
            $query->where('slug', $slug);
        });
        $query->when($search, function ($query, $search) use ($searchColumns) {
            $query->where(function ($q) use ($search, $searchColumns) {
                foreach ($searchColumns as $column) {
                    $q->orWhere($column, 'LIKE', '%' . $search . '%');
                }
            });
        });

        // Sort by position ASC
        return $query->orderBy('position', 'asc')->get();
    }
}
