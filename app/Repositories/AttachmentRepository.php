<?php

namespace App\Repositories;

use App\Models\Attachments;
use App\Repositories\BaseRepository;

/**
 * Class AttachmentRepository
 *
 * Repository class for interacting with the `Attachments` model.
 *
 * @package App\Repositories
 */
class AttachmentRepository extends BaseRepository
{
    /**
     * AttachmentRepository constructor.
     *
     * @param Attachments $model The underlying model for the repository.
     */
    public function __construct(Attachments $model)
    {
        $this->model = $model;
    }
}
