<?php

namespace App\Repositories;

use App\Models\AssetMeta;
use App\Repositories\BaseRepository;

/**
 * Class AssetMetaRepository
 *
 * Repository class for interacting with the `Assets` model.
 *
 * @package App\Repositories
 */
class AssetMetaRepository extends BaseRepository
{
    /**
     * AssetRepository constructor.
     *
     * @param Assets $model The underlying model for the repository.
     */
    public function __construct(AssetMeta $model)
    {
        $this->model = $model;
    }
}
