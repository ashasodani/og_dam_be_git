<?php

namespace App\Repositories;

use App\Models\Portals;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class PortalRepository
 *
 * Repository class for interacting with the `Portal` model.
 *
 * @package App\Repositories
 */
class PortalRepository extends BaseRepository
{
    /**
     * PortalRepository constructor.
     *
     * @param Portal $model The underlying model for the repository.
     */
    public function __construct(Portals $model)
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
    public function relatedWithPortals($request): Collection
    {
        $relation = $request->has('include') ? $request->get('include') : [];
        $slug = request()->input('slug');
        $append['sort_by'] = $append['sort_by']??'created_at';
        $append['sort_order'] = $append['sort_order']??'desc';
      
        $query = Portals::with(['getrelatedPortal' => function($query) use ($slug) {
            $query->where('portal_id', $slug);
        }])->whereHas('getrelatedPortal', function ($query) use ($slug) {
            $query->where('portal_id', $slug);
        });
       
        $data= $query->orderBy($append['sort_by'], $append['sort_order'])->get();
        return $data;
    }
}
