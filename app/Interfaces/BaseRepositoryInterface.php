<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface BaseRepositoryInterface
 *
 * @package App\Contracts
 */
interface BaseRepositoryInterface
{
    /**
     * Get all query.
     *
     * @param array $columns Columns to select (default: ['*']).
     * @param array $relations Eager load relations (default: []).
     * @return Builder Query builder instance.
     */
    public function query(array $columns = ['*'], array $relations = []): Builder;

    /**
     * Get all Pagination query.
     *
     * @param array $columns Columns to select (default: ['*']).
     * @param array $relations Eager load relations (default: []).
     * @return LengthAwarePaginator  LengthAwarePaginator instance.
     */
    public function pagination(array $columns = ['*'], array $relations = [], array $appends = []): LengthAwarePaginator;

     /**
     * Get all collection.
     *
     * @param array $columns Columns to select (default: ['*']).
     * @param array $relations Eager load relations (default: []).
     * @return Collection Query Collection instance.
     */
    public function allRelation(array $columns = ['*'], array $relations = []): Collection;


    /**
     * Get all models.
     *
     * @param array $columns Columns to select (default: ['*']).
     * @param array $relations Eager load relations (default: []).
     * @return Collection Collection of models.
     */
    public function all(array $columns = ['*'], array $relations = []): Collection;

    /**
     * Get all trashed models.
     *
     * @return Collection Collection of trashed models.
     */
    public function allTrashed(): Collection;

    /**
     * Find model by id.
     *
     * @param int $modelId ID of the model to find.
     * @param array $columns Columns to select (default: ['*']).
     * @param array $relations Eager load relations (default: []).
     * @param array $appends Additional attributes (default: []).
     * @return Model|null Model instance if found, otherwise null.
     */
    public function findById(
        int $modelId,
        array $columns = ['*'],
        array $relations = [],
        array $appends = []
    ): ?Model;

    /**
     * Find model by UUid.
     *
     * @param string $modelUuid
     * @param array $columns
     * @param array $relations
     * @param array $appends
     * @return Model
     */
    public function findByUuid(
        string $modelUuid,
        array $columns = ['*'],
        array $relations = [],
        array $appends = []
    ): ?Model;

    /**
     * Find trashed model by id.
     *
     * @param int $modelId ID of the trashed model to find.
     * @return Model|null Trashed model instance if found, otherwise null.
     */
    public function findTrashedById(int $modelId): ?Model;

    /**
     * Find only trashed model by id.
     *
     * @param int $modelId ID of the trashed model to find.
     * @return Model|null Only trashed model instance if found, otherwise null.
     */
    public function findOnlyTrashedById(int $modelId): ?Model;

    /**
     * Create a model.
     *
     * @param array $payload Data to create a model.
     * @return Model|null Created model instance, or null on failure.
     */
    public function create(array $payload): ?Model;
    
    /**
     * Create a model.
     *
     * @param array $payload Data to insert a model.
     * @return bool|null Created model instance, or null on failure.
     */
    public function insert(array $payload): ?bool;

    /**
     * Update existing model.
     *
     * @param int $modelId ID of the model to update.
     * @param array $payload Data to update the model.
     * @return Model True if update successful, false otherwise.
     */
    public function update(int $modelId, array $payload): Model;

     /**
     * Update existing model.
     *
     * @param string $modelUuid
     * @param array $payload
     * @return bool
     */
    public function updateByUuid(string $modelUuid, array $payload): bool;

    /**
     * Delete model by id.
     *
     * @param int $modelId ID of the model to delete.
     * @return bool True if deletion successful, false otherwise.
     */
    public function deleteById(int $modelId): bool;

    /**
     * Delete model by uuid.
     *
     * @param string $modelUuid
     * @return bool
     */
    public function deleteByUuid(string $modelUuid): bool;

    /**
     * Restore model by id.
     *
     * @param int $modelId ID of the model to restore.
     * @return bool True if restoration successful, false otherwise.
     */
    public function restoreById(int $modelId): bool;

    /**
     * Permanently delete model by id.
     *
     * @param int $modelId ID of the model to permanently delete.
     * @return bool True if deletion successful, false otherwise.
     */
    public function permanentlyDeleteById(int $modelId): bool;
}
