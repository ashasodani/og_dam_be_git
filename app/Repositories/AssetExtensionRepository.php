<?php
namespace App\Repositories;

use App\Models\AssetExtension;
use App\Repositories\BaseRepository;

/**
 * Class AssestRepository
 *
 * Repository class for interacting with the `Assets` model.
 *
 * @package App\Repositories
 */
class AssetExtensionRepository extends BaseRepository
{
    /**
     * AssetRepository constructor.
     *
     * @param Assets $model The underlying model for the repository.
     */
    public function __construct(AssetExtension $model)
    {
        $this->model = $model;
    }
    /**
     * Retrieve the first record that matches the attributes or create it.
     *
     * @param array $payload The attributes and values to search for and create.
     *
     * @return Model The first existing model or the newly created model.
     */

    public function firstOrCreate(array $payload)
    {
        $this->model->firstOrCreate($payload, $payload);

    }
    /**
     * Retrieve all extensions of the assets.
     *
     * @return array The list of extensions.
     */
    public function getExtension()
    {
        return $this->model
            ->whereNotNull('extension')
            ->where('extension', '!=', '')
            ->pluck('extension')
            ->unique()
            ->toArray();
    }
}
