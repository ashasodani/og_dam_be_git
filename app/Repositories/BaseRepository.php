<?php

namespace App\Repositories;

use App\Interfaces\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use App\Notifications\CommonAppNotification;
use App\Notifications\CommonMailNotification;
use Intervention\Image\Facades\Image;
use Carbon\Carbon;



/**
 * Class BaseRepository
 *
 * This class provides a base implementation for repository pattern using Eloquent models.
 * It implements common CRUD operations and provides methods for handling soft deletes.
 *
 * @package App\Repositories
 */
class BaseRepository implements BaseRepositoryInterface
{
    /**
     * @var Model The Eloquent model instance.
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param Model $model The Eloquent model instance.
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Get the query builder instance.
     *
     * @param array $columns   The columns to be selected.
     * @param array $relations The relationships to be eager loaded.
     *
     * @return Builder
     */
    public function query(array $columns = ['*'], array $relations = []): Builder
    {
        return $this->model->query()->select($columns)->with($relations);
    }

    /**
     * Get the query paginator instance.
     *
     * @param array $columns   The columns to be selected.
     * @param array $relations The relationships to be eager loaded.
     *
     * @return LengthAwarePaginator
     */
    public function pagination(array $columns = ['*'], array $relations = [], array $appends = [], $searchColumns = []): LengthAwarePaginator
    {
        $search = $appends['search'] ?? '';
        $query  = $this->query($columns, $relations);
        $query->when($search, function ($query, $search) use ($searchColumns) {
            $query->where(function ($q) use ($search, $searchColumns) {
                foreach ($searchColumns as $column) {
                    $q->orWhere($column, 'LIKE', '%' . $search . '%');
                }
            });
        });
        $query->with($relations);
        $data = $query->paginate($appends['per_page']);
        return $data;
    }

    /**
     * Get the query collection instance.
     *
     * @param array $columns   The columns to be selected.
     * @param array $relations The relationships to be eager loaded.
     *
     * @return Collection
     */
    public function allRelation(array $columns = ['*'], array $relations = []): Collection
    {
        return $this->query($columns, $relations)->with($relations)->orderby('created_at', 'desc')->get();
    }

    /**
     * Retrieve all models from the database.
     *
     * @param array $columns   The columns to be selected.
     * @param array $relations The relationships to be eager loaded.
     *
     * @return Collection
     */
    public function all(array $columns = ['*'], array $relations = []): Collection
    {
        return $this->query($columns, $relations)->get();
    }

    /**
     * Retrieve all trashed models from the database.
     *
     * @return Collection
     */
    public function allTrashed(): Collection
    {
        return $this->model->onlyTrashed()->get();
    }

    /**
     * Find a model by its primary key.
     *
     * @param int   $modelId   The ID of the model.
     * @param array $columns   The columns to be selected.
     * @param array $relations The relationships to be eager loaded.
     * @param array $appends   The attributes to append to the model.
     *
     * @return Model|null
     */
    public function findById(
        int $modelId,
        array $columns = ['*'],
        array $relations = [],
        array $appends = []
    ): ?Model {
        return $this->model->select($columns)->with($relations)->findOrFail($modelId)->append($appends);
    }

    /**
     * Find a model by its UUID.
     *
     * @param string $modelUuid The UUID of the model.
     * @param array  $columns   The columns to be selected.
     * @param array  $relations The relationships to be eager loaded.
     * @param array  $appends   The attributes to append to the model.
     *
     * @return Model|null
     */
    public function findByUuid(
        string $modelUuid,
        array $columns = ['*'],
        array $relations = [],
        array $appends = []
    ): ?Model {
        return $this->model->select($columns)->with($relations)->where('uuid', $modelUuid)
            ->firstOrFail()->append($appends);
    }

    /**
     * Find a trashed model by its primary key.
     *
     * @param int $modelId The ID of the trashed model.
     *
     * @return Model|null
     */
    public function findTrashedById(int $modelId): ?Model
    {
        return $this->model->withTrashed()->findOrFail($modelId);
    }

    /**
     * Find a trashed model by its primary key.
     *
     * @param int $modelId The ID of the trashed model.
     *
     * @return Model|null
     */
    public function findOnlyTrashedById(int $modelId): ?Model
    {
        return $this->model->onlyTrashed()->findOrFail($modelId);
    }

    /**
     * Create a new model and persist it to the database.
     *
     * @param array $payload The data for the new model.
     *
     * @return Model
     */
    public function create(array $payload): Model
    {
        return tap(
            $this->model->create($payload),
            function ($model) {
                $model->refresh();
            }
        );
    }


    /**
     * Update an existing model in the database.
     *
     * @param int   $modelId The ID of the model to be updated.
     * @param array $payload The updated attributes.
     *
     * @return Model
     */
    public function update(int $modelId, array $payload): Model
    {
        // dd($payload,$modelId);
        $model = $this->findById($modelId);
        $model->update($payload);
        return $model->fresh();
    }

    /**
     * Update an existing model in the database by UUID.
     *
     * @param string $modelUuid The UUID of the model to be updated.
     * @param array  $payload   The updated attributes.
     *
     * @return bool
     */
    public function updateByUuid(string $modelUuid, array $payload): bool
    {
        $model = $this->findByUuid($modelUuid);

        return $model->update($payload);
    }

    /**
     * Delete a model from the database by its primary key.
     *
     * @param int $modelId The ID of the model to be deleted.
     *
     * @return bool
     */
    public function deleteById(int $modelId): bool
    {
        //dd($modelId);
        return $this->findById($modelId)->delete();
    }

    /**
     * Delete a model from the database by its UUID.
     *
     * @param string $modelUuid The UUID of the model to be deleted.
     *
     * @return bool
     */
    public function deleteByUuid(string $modelUuid): bool
    {
        return $this->findByUuid($modelUuid)->delete();
    }

    /**
     * Restore a soft-deleted model by its primary key.
     *
     * @param int $modelId The ID of the model to be restored.
     *
     * @return bool
     */
    public function restoreById(int $modelId): bool
    {
        return $this->findOnlyTrashedById($modelId)->restore();
    }

    /**
     * Permanently delete a trashed model by its primary key.
     *
     * @param int $modelId The ID of the trashed model to be permanently deleted.
     *
     * @return bool
     */
    public function permanentlyDeleteById(int $modelId): bool
    {
        return $this->findTrashedById($modelId)->forceDelete();
    }

    /**
     * Insert a new model in the database.
     *
     * @param array $payload The data for the new model.
     *
     * @return bool The created model data.
     */
    public function insert(array $payload): bool
    {
        return $this->model->insert($payload);
    }

    /**
     * purpose upload  attachment in specific folder
     * @param object $document
     * @param int $type
     * @return bool
     */
    public function uploadMaster($document, $filePath)
    {

        try {
            $path = Storage::disk('s3')->put($filePath, file_get_contents($document));

            $url = Storage::disk('s3')->url($path);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
    /**
     * Resize and process an image to a specific dimension and format.
     *
     * This function takes an original image, resizes it to 422x268 pixels while maintaining 
     * the aspect ratio, adds padding to fit the exact dimensions, sharpens the image, and
     * encodes it in the WebP format with high quality.
     *
     * @param mixed $originalImage The original image file to be processed.
     * 
     * @return \Intervention\Image\Image The processed image.
     */

    public function imageResize($originalImage, $height = 262, $width = 162)
    {
        // $thumbnail = Image::make($originalImage)
        //     ->orientate() // Fix mobile orientation
        //     ->resize($width, $height, function ($constraint) {
        //         $constraint->aspectRatio();
        //         $constraint->upsize();
        //     })
        //     ->resizeCanvas($width, $height, 'center', false, [0, 0, 0, 0])
        //     ->sharpen(10)
        //     ->encode('webp', 100);
        // return $thumbnail;
        $padding = 30; // pixels of padding around

        $thumbnail = Image::make($originalImage)
            ->orientate() // Fix mobile orientation
            ->resize($width - ($padding * 2), $height - ($padding * 2), function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })
            ->resizeCanvas($width, $height, 'center', false, [255, 255, 255, 0]) // white with transparent alpha
            ->sharpen(10)
            ->encode('webp', 100);

        return $thumbnail;
    }
    /**
     * Return the file name of the thumbnail based on the given document file.
     *
     * @param object $documentfile The document file.
     *
     * @return string The file name of the thumbnail.
     */
    public function getThumFileName($documentPath)
    {
        $timestamp = time();
        $filename = "{$timestamp}.webp";
        $documentPath = $documentPath . $filename;
        return $documentPath;
    }
    /**
     * purpose upload  attachment in specific folder
     * @param object $document
     * @param int $type
     * @return bool
     */
    public function thumbUploadMaster($document, $filePath)
    {

        try {
            $path = Storage::disk('s3')->put($filePath, (string)$document);

            $path = Storage::disk('s3')->url($path);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
    public function findMany(array $ids): Collection
    {
        return $this->model->whereIn('id', $ids)->get();
    }

    public function NotifyUser($user, $title, $message, $url, $type, $loginUser, $is_mail = false, $is_app = false, $nameObject = null, $asset = [])
    {
        if (!$user) {
            return null;
        }
        if ($is_mail) {
            $user->notify(new CommonMailNotification($title, $message, $url, $type, $user, $loginUser, $nameObject, $asset));
        }
        if ($is_app) {
            $user->notify(new CommonAppNotification($title, $message, $url, $type, $loginUser));
        }
    }
    /**
     * Apply filters to the query.
     *
     * @param Builder $query
     * @param array $extension
     * @param array $tagIds
     * @param array $labelIds
     * @param array $collectionIds
     * @param string|null $upload_on
     * @param string|null $startDate
     * @param string|null $endDate
     * @param string|null $search
     * @param int|null $recentUpload
     * @return Builder
     */
    public function applyAssetFilters($query, $extension, $tagIds, $labelIds, $collectionIds, $upload_on, $startDate, $endDate, $search, $recentUpload = null)
    {
        if (!empty($extension)) {
            $query->whereIn('extension', $extension);
        }

        if (!empty($recentUpload)) {
            $query->where('recent_upload', $recentUpload);
        }
        if (!empty($tagIds)) {
            $query->whereHas('tags', fn($q) => $q->whereIn('tags.id', $tagIds));
        }

        if (!empty($labelIds)) {
            $query->whereHas('labels', fn($q) => $q->whereIn('labels.id', $labelIds));
        }
        if (!empty($collectionIds)) {
            // dd($collectionIds);
            $query->whereHas('collections', fn($q) => $q->whereIn('collections.id', $collectionIds));
        }

        if (!empty($upload_on)) {
            switch ($upload_on) {
                case '30min':
                    $query->where('assets.created_at', '>=', now()->subMinutes(30));
                    break;
                case '24hours':
                    $query->where('assets.created_at', '>=', now()->subDay());
                    break;
                case '7days':
                    $query->where('assets.created_at', '>=', now()->subDays(7));
                    break;
                case 'recent_upload':
                    $query->where('assets.created_at', '>=', now()->subDay());
                    break;
                case 'range':
                    $start = Carbon::parse($startDate)->startOfDay();
                    $end = Carbon::parse($endDate)->endOfDay();
                    $query->whereBetween('assets.created_at', [$start, $end]);
                    break;
            }
        }

        if (!empty($search)) {
            $this->applyAssetSearchFilter($query, $search);
        }

        return $query;
    }
    /**
     * Apply a date filter to the query based on the specified upload time range.
     *
     * This function modifies the given query to filter assets by their creation date,
     * according to the specified `$upload_on` period. The available periods are:
     * - '30min': Within the last 30 minutes.
     * - '24hours': Within the last 24 hours.
     * - '7days': Within the last 7 days.
     * - 'recent_upload': Within the last 24 hours.
     * - 'range': A custom date range specified by `$startDate` and `$endDate`.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder instance to apply filters to.
     * @param string $upload_on The time range for filtering assets.
     * @param string|null $startDate The start date for the 'range' option.
     * @param string|null $endDate The end date for the 'range' option.
     * @return void
     */

    protected function applyUploadOnFilter($query, $upload_on, $startDate, $endDate)
    {
        switch ($upload_on) {
            case '30min':
                $query->where('assets.created_at', '>=', now()->subMinutes(30));
                break;
            case '24hours':
                $query->where('assets.created_at', '>=', now()->subDay());
                break;
            case '7days':
                $query->where('assets.created_at', '>=', now()->subDays(7));
                break;
            case 'recent_upload':
                $query->where('assets.created_at', '>=', now()->subDay());
                break;
            case 'range':
                $start = Carbon::parse($startDate)->startOfDay();
                $end   = Carbon::parse($endDate)->endOfDay();
                $query->whereBetween('assets.created_at', [$start, $end]);
                break;
        }
    }


    /**
     * Applies a search filter to the query based on the provided search term.
     *
     * This function modifies the given query to filter results where the
     * asset's name, filename, description, or extension starts with the
     * specified search term. Additionally, it applies the filter to related
     * tags, labels, and collections by checking if their names start with
     * the search term.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder instance to apply filters to.
     * @param string $search The search term used for filtering.
     * @return \Illuminate\Database\Eloquent\Builder The modified query builder instance.
     */

    public function applyAssetSearchFilter($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'ilike', "{$search}%")
                ->orWhere('filename', 'ilike', "{$search}%")
                ->orWhere('asset_key', 'ilike', "{$search}%")
                ->orWhere('description', 'ilike', "{$search}%")
                ->orWhere('extension', 'ilike', "{$search}%")
                ->orWhereHas('tags', fn($tq) => $tq->where('name', 'ilike', "{$search}%"))
                ->orWhereHas('labels', fn($lq) => $lq->where('name', 'ilike', "{$search}%"))
                ->orWhereHas('collections', fn($lq) => $lq->where('name', 'ilike', "{$search}%"));
        });
    }
}
