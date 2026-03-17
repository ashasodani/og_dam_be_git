<?php

namespace App\Services;

use App\Models\Assets;
use App\Models\ShareLinks;
use App\Models\Tags;

use App\Repositories\AssetMetaRepository;
use App\Repositories\AssetRepository;
use App\Repositories\AttachmentRepository;
use App\Repositories\AssetExtensionRepository;
use App\Repositories\LabelRepository;
use App\Repositories\SectionRepository;
use App\Repositories\SubFolderRepository;
use Illuminate\Container\Attributes\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Notifications\AssetAppNotification;
use App\Notifications\ShareLinkEmailNotification;
use App\Models\User;
use OwenIt\Auditing\Models\Audit;
use App\Http\Requests\Tag\TagCreateRequest;
use Illuminate\Support\Facades\Validator;

/**
 * Class AssetService
 * Service class for managing CRUD operations of Asset
 * @package App\Services
 */
class AssetService
{
    /**
     * @var AssetRepository Repository for interacting with the Asset data
     */
    protected $assetRepository;

    /**
     * @var SectionRepository Repository for interacting with the section data
     */
    protected $sectionRepository;

    /**
     * @var AttachmentRepository Repository for interacting with the Asset data
     */
    protected $subfolderRepository;

    /**
     * @var AssetMetaRepository Repository for interacting with the Asset data
     */
    protected $assetMetaRepository;

    /**
     * @var LabelRepository Repository for interacting with the Asset data
     */
    protected $labelRepository;

    /**
     * @var AssetExtensionRepository Repository for interacting with the Asset data
     */
    protected $assetExtensionRepository;
    /**
     * AssetService constructor.
     * @param AssetRepository $assetRepository The repository for interacting with Asset data.
     */
    public function __construct(AssetRepository $assetRepository, AssetMetaRepository $assetMetaRepository, SectionRepository $sectionRepository, SubFolderRepository $subfolderRepository, LabelRepository $labelRepository, AssetExtensionRepository $assetExtensionRepository)
    {
        $this->assetRepository     = $assetRepository;
        $this->sectionRepository   = $sectionRepository;
        $this->subfolderRepository = $subfolderRepository;
        $this->assetMetaRepository = $assetMetaRepository;
        $this->labelRepository     = $labelRepository;
        $this->assetExtensionRepository    = $assetExtensionRepository;
    }
    public function createAsset($data): Model
    {
        $uploadedFiles = [];

        foreach ($data->file('attachments') as $file) {

            // $documentFile = $data->file('attachments');
            // $documentPath = config('deegest.Asset_attchment.default_file_path');
            // $documentFiles = !empty($documentFile) ? $this->AssetRepository->uploadMaster($documentFile, $documentPath) : '';
            $path            = $file->store('attachments', 'public');
            $uploadedFiles[] = [
                'original_name' => $file->getClientOriginalName(),
                'stored_path'   => $path,
                'mime_type'     => $file->getMimeType(),
                'size'          => $file->getSize(),
                'created_by'          => Auth::id(),
            ];
            $data['filename']  = $file->getClientOriginalName();
            $data['asset_url'] = $path;
            $data['extension'] = $file->getClientOriginalExtension();
            $data['asset_key'] = substr(Str::random(20), 0, 14);
            // dd($data->all());
            $result = $this->assetRepository->create($data->all());

            if (! empty($data['section_id'])) {
                $result->sections()->attach($data['section_id']);
            }
            if (! empty($data['subfolder_id'])) {
                $result->subfolders()->attach($data['subfolder_id']);
            }
            if (! empty($data['label_id'])) {
                $result->labels()->attach($data['label_id']);
            }

            $this->assetMeta($result->id, $file);
        }

        return $result;
    }

    /**
     * Create a new Asset.
     * @param array $AssetData The data for creating the Asset.
     * @return Model The created Asset data.
     */
    public function createTypeAsset($request): Model
    {
        // $file = $request->file('attachments');
        //$path            = $file->store('attachments', 'public');
        // $uploadedFiles[] = [
        //     'original_name' => $file->getClientOriginalName(),
        //     'stored_path'   => $path,
        //     'mime_type'     => $file->getMimeType(),
        //     'size'          => $file->getSize(),
        // ];
        $data = $request->all();


        $data['created_by'] = Auth::user()->id;
        $data['filename']  = $request['name'];
        $data['is_completed'] = (bool)true;
        $data['created_by']  = Auth::user()->id;
        $data['asset_key'] = substr(Str::random(20), 0, 14);
        //dd($data);

        $result = $this->assetRepository->create($data);
        if ($request->hasFile('thumbnail')) {
            $originalImage = $request->file('thumbnail');
            $thumbnail = $this->assetRepository->imageResize($originalImage);
            $documentfile  = $thumbnail;
            $docPath = config('deegest.asset_document.asset_file_path');
            $documentPath = $this->assetRepository->getThumFilename($docPath);
            $documentFiles  = ! empty($documentfile) ? $this->assetRepository->thumbUploadMaster($documentfile, $documentPath) : '';
            $data['thumbnail_image'] = $request->file('thumbnail')->getClientOriginalName();
            $data['url']             = $documentPath;
            $this->assetRepository->update($result->id, $data);
        }

        if (! empty($data['workspace_id'])) {
            $result->workspaces()->attach($request['workspace_id']);
        }
        if (! empty($data['section_id'])) {
            $result->sections()->attach($request['section_id']);
        }
        if (! empty($data['subfolder_id'])) {
            $result->subfolders()->attach($request['subfolder_id']);
        }
        if (! empty($data['tags'])) {
            $result->tags()->detach();
            $tagWorkspaceId = $request['workspace_id'];
            $tagArray = json_decode($data['tags']);
            $this->AttachTags($result, $tagArray, $tagWorkspaceId);
             /* add entry in audit log table start */
            $newTagIdName = Tags::whereIn('name', $tagArray)->pluck('id')->toArray();
            $newTagIds = $newTagIdName;
            $oldTagIds = $result->tags()->pluck('tags.name')->toArray();
            $this->addPivotDataLog($oldTagIds, $newTagIds, $result, 'tag');
            /* add entry in audit log table end */
          
        }
        // if (! empty($data['tags'])) {
        //     $tags = json_decode($request['tags']);
        //     $result->tags()->detach();
        //     foreach ($tags as $record) {
        //         // Tag create and update
        //         // $data = Tags::firstOrCreate(
        //         //     ['name' => $record],
        //         //     ['name' => $record]
        //         // );
        //         $result->tags()->attach($record);
        //         //$data->workspaces()->attach($request['workspace_id']);
        //     }
        // }
        if (! empty($request['labels'])) {
            $tags = json_decode($request['labels']);
            foreach ($tags as $record) {
                $result->labels()->attach($record);
            }
        }
        if ($request->hasFile('thumbnail')) {
            $this->assetMeta($result->id, $request->file('thumbnail'));
        }

        return $result;
    }

    /**
     * Get the query builder for Asset.
     * @return Builder
     */
    public function getAssetQuery(): Builder
    {
        return $this->assetRepository->query();
    }

    /**
     * Get the query Collection for Asset.
     * @return Collection
     */
    public function getAssetCollection($request): Collection
    {
        return $this->assetRepository->getAssetData($request);
    }

    /**
     * Find an Asset by their ID.
     * @param int $AssetID The ID of the workspace.
     * @return Model|null The workspace model or null if not found.
     */
    public function findByAssetId(int $Id, object $request): ?Model
    {
        $include = $request->has('include') ? json_decode($request->get('include')) : [];
        return $this->assetRepository->findById($Id, ['*'], $include);
    }
    /**
     * Find an Asset by their ID.
     * @param int $AssetID The ID of the workspace.
     * @return Model|null The workspace model or null if not found.
     */
    public function findByAssetsKey(string $assetKey, object $request): ?Model
    {
        $include = $request->has('include') ? json_decode($request->get('include')) : [];
       // dd($request->get('include'));
        $aasetId =  Assets::where('asset_key',$assetKey)->value('id');
        return $this->assetRepository->findById($aasetId, ['*'], $include);
    }

    /**
     * Update an existing Asset.
     * @param int $AssetId The ID of the Asset to be updated.
     * @param array  $AssetData The data for updating the workspace.
     * @return model True on successful update, false otherwise.
     */
    public function updateAsset(int $assetId, object $assetData): Model
    {

        $data = $assetData->all();
        if ($assetData->has('thumbnail')) {
            // $documentfile =  $assetData->file('thumbnail');
            // $documentPath = $this->getFileName($documentfile,$assetId);
            // $documentFiles = !empty($documentfile) ? $this->assetRepository->uploadMaster($documentfile, $documentPath) : '';
            $originalImage = $assetData->file('thumbnail');
            $thumbnail = $this->assetRepository->imageResize($originalImage);
            $documentfile  = $thumbnail;
            $docPath = config('deegest.asset_document.asset_file_path');
            $documentPath = $this->assetRepository->getThumFilename($docPath);
            $documentFiles                    = ! empty($documentfile) ? $this->assetRepository->thumbUploadMaster($documentfile, $documentPath) : '';
            $data['thumbnail_image'] = $assetData->file('thumbnail')->getClientOriginalName();
            $data['url']             = $documentPath;

            // $path                         = $assetData->file('thumbnail')->store('attachments', 'public');
            //$data['thumbnail_image']            = $path;
        }
        if ($assetData->is_thumbnail === "no") {
            $data['thumbnail_image'] = null;
            $data['url']             = null;
        }
        $asset = $this->assetRepository->update($assetId, $data);

        if (! empty($assetData['section_id'])) {
            $asset->sections()->sync([$assetData['section_id']]);
        }
        if (! empty($assetData['tags'])) {
             /* add entry in audit log table start */
            $tagArray = json_decode($assetData['tags']);
            $newTagIdName = Tags::whereIn('name', $tagArray)->pluck('id')->toArray();
            $newTagIds = $newTagIdName;
            $oldTagIds = $asset->tags()->pluck('tags.name')->toArray();
            $this->addPivotDataLog($oldTagIds, $tagArray, $asset, 'tag');
            /* add entry in audit log table end */
            $tagWorkspaceId = $assetData['workspace_id'];
           
            $asset->tags()->detach();
            $this->AttachTags($asset, $tagArray, $tagWorkspaceId);
            
          
        }
        if (! empty($assetData['labels'])) {
            /* add entry in audit log table start */
            $newLabelIds = json_decode($assetData['labels']);
            $oldLabelIds = $asset->labels()->pluck('labels.name')->toArray();
            $this->addPivotDataLog($oldLabelIds, $newLabelIds, $asset, 'label');
            /* add entry in audit log table end */
            $labels = json_decode($assetData['labels']);
            $asset->labels()->detach();
            foreach ($labels as $record) {
                $asset->labels()->attach($record);
            }
        }
        return $asset;
    }
     /**
     * Update an existing Asset.
     * @param int $AssetId The ID of the Asset to be updated.
     * @param array  $AssetData The data for updating the workspace.
     * @return model True on successful update, false otherwise.
     */
    public function updateTagAsset(int $assetId, object $assetData): Model
    {
        $asset = $this->assetRepository->findById($assetId);
         $asset->tags()->detach();
        if (! empty($assetData['tags'])) {
            $newTagIdName = Tags::whereIn('name', $assetData['tags'])->pluck('id')->toArray();
          
            $newTagIds = $newTagIdName;
            $oldTagIds = $asset->tags()->pluck('tags.name')->toArray();
            $this->addPivotDataLog($oldTagIds, $assetData['tags'], $asset, 'tag');
            $tagWorkspaceId = $assetData['workspace_id'];
            $this->AttachTags($asset, $assetData['tags'], $tagWorkspaceId);
            
            /* add entry in audit log table end */
          
        }
        return $asset;
    }
    /**
     * Update an existing Asset labels.
     * @param int $assetId The ID of the Asset to be updated.
     * @param array  $assetData The data for updating the workspace.
     * @return model True on successful update, false otherwise.
     */

    public function updateLabelAsset(int $assetId, object $assetData): Model
    {
        $asset = $this->assetRepository->findById($assetId);
         $asset->labels()->detach();
        if (! empty($assetData['labels'])) {
            /* add entry in audit log table start */
            $newLabelIds = $assetData['labels'];
            $oldLabelIds = $asset->labels()->pluck('labels.name')->toArray();
            $this->addPivotDataLog($oldLabelIds, $newLabelIds, $asset, 'label');
            /* add entry in audit log table end */
           
            foreach ($assetData['labels'] as $record) {
                $asset->labels()->attach($record);
            }
          
        }
        return $asset;
    }
    public function AttachTags($asset, $tags, $tagWorkspaceId)
    {
        //$tags = json_decode($tags);
          foreach ($tags as $record) {
                $exists = \DB::table('tags')
                    ->join('workspace_tags', 'tags.id', '=', 'workspace_tags.tag_id')
                    ->where('workspace_tags.workspace_id', $tagWorkspaceId)
                    ->whereRaw('LOWER(tags.name) = ?', [$record])
                    ->whereNull('tags.deleted_at')
                    ->exists();
                    if($exists){
                        $tagdata = Tags::where('name', $record)->first();
                      $asset->tags()->attach($tagdata->id);
                    }else{
                      $TagData = Tags::firstOrCreate(
                        ['name' => $record],
                        ['name' => $record]
                      );
                      $asset->tags()->attach($TagData->id);
                      $TagData->workspaces()->attach($tagWorkspaceId);
                    }
            }
    }
    /**
     * Deleting an existing Asset.
     * @param int $assetId The id of the workspace to be deleted.
     * @return bool True on successful deletion, false otherwise.
     */
    public function deleteAssetById(int $assetId): bool
    {
        return $this->assetRepository->deleteById($assetId);
    }

    /**
     * Deleting an existing Asset.
     * @param array $assetId The id of the workspace to be deleted.
     * @return Mixed True on successful deletion, false otherwise.
     */
    public function bulkAssetDelete(array $data): Mixed
    {
        //  dd($data['asset_id']);
        $Asset = Assets::whereIn('id', $data['asset_id'])->get();
        foreach ($Asset as $asses) {
            $asses->sections()->detach();
            $asses->subfolders()->detach();
            $asses->delete();
        }

        return $Asset;
    }

    /**
     * Move the given Assets to the given section.
     *
     * @param int $sectionId The ID of the section to move the Assets to.
     * @param array $AssetIds The IDs of the Assets to be moved.
     *
     * @return Model The section model with the Assets associated.
     */
    public function moveAssetWithLocations($data): Model
    {
        //dd($data['section_id']);
        $fromSectionData = $this->sectionRepository->findById($data['from_section_id'], ['*'], []);
        $toSectionData   = $this->sectionRepository->findById($data['to_section_id'], ['*'], []);

        $fromSubFolderData = $this->subfolderRepository->findById($data['from_subfolder_id'], ['*'], ['sections']);
        $toSubFolderData   = $this->subfolderRepository->findById($data['to_subfolder_id'], ['*'], []);

        foreach ($data['asset_id'] as $assetData) {
            // detach with old section id
            if (! empty($data['from_section_id'])) {
                $fromSectionData->assets()->detach($assetData);
            }

            if (! empty($data['from_subfolder_id'])) {
                $fromSubFolderData->assets()->detach($assetData);
            }

            // Sync Assets with new section id
            $toSectionData->assets()->syncWithoutDetaching([$assetData]);

            $toSubFolderData->assets()->syncWithoutDetaching([$assetData]);
        }
        return $toSectionData;
    }

    /**
     * Copy the given Assets to the given section.
     *
     * @param int $sectionId The ID of the section to copy the Assets to.
     * @param array $AssetIds The IDs of the Assets to be copied.
     *
     * @return Model The section model with the Assets associated.
     */
    public function copyAssetWithLocations($data): Model
    {
        $toSectionData   = $this->sectionRepository->findById($data['to_section_id'], ['*'], []);
        $toSubFolderData = $this->subfolderRepository->findById($data['to_subfolder_id'], ['*'], []);

        foreach ($data['asset_id'] as $assetData) {

            $toSectionData->assets()->syncWithoutDetaching([$assetData]);

            $toSubFolderData->assets()->syncWithoutDetaching([$assetData]);
        }
        return $toSectionData;
    }

    /**
     * Merge the given Assets to the given section.
     *
     * @param int $sectionId The ID of the section to Merge the Assets to.
     * @param array $AssetIds The IDs of the Assets to be copied.
     *
     * @return Model The section model with the Assets associated.
     */
    public function mergeAssetWithLocations($data): Model
    {
        $toSectionData   = $this->sectionRepository->findById($data['to_section_id'], ['*'], []);
        $toSubFolderData = $this->subfolderRepository->findById($data['to_subfolder_id'], ['*'], []);

        foreach ($data['asset_id'] as $assetData) {
            $toSectionData->assets()->syncWithoutDetaching([$assetData]);

            $toSubFolderData->assets()->syncWithoutDetaching([$assetData]);
        }
        return $toSectionData;
    }

    /**
     * Merge the given Assets to the specified section and subfolder.
     *
     * @param array $data The data containing IDs for section, subfolder, and assets.
     * @return Model The section model with the Assets associated.
     */
    public function assetMeta($assetId, $file, $filesize = 0)
    {
        $data = [
            [
                'name'       => 'original_name',
                'asset_id'   => $assetId,
                'value'      => $file->getClientOriginalName(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name'       => 'extension',
                'asset_id'   => $assetId,
                'value'      => $file->getClientOriginalExtension(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name'       => 'mime_type',
                'asset_id'   => $assetId,
                'value'      => $file->getMimeType(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name'       => 'size',
                'asset_id'   => $assetId,
                'value'      => $filesize ? $filesize : $file->getSize(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name'       => 'temp_path',
                'asset_id'   => $assetId,
                'value'      => $file->getRealPath(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];
        return $this->assetMetaRepository->insert($data);
    }

    /**
     * Create a thumbnail for the given Asset.
     *
     * @param Request $data The data containing the asset id and thumbnail image.
     *
     * @return Model The updated asset model.
     */
    public function createThumbnails($data)
    {
        $documentfile =  $data->file('thumbnail');
        $documentPath = $this->getFileName($documentfile);
        $documentFiles = !empty($documentfile) ? $this->assetRepository->uploadMaster($documentfile, $documentPath) : '';
        $assetData['thumbnail_image']             = $documentPath;

        return $this->assetRepository->update($data['asset_id'], $assetData);
    }
    /**
     * Make file name for asset
     * @param file $documentfile The data for creating the workspace.
     * 
     */

    public function getFileName($documentfile, $assetId)
    {
        $documentPath = config('deegest.asset_document.asset_file_path');
        $extension    = $documentfile->getClientOriginalExtension();
        $fileName     =  $documentfile->getClientOriginalName();
        $documentPath = $documentPath . $assetId . '/' . $fileName;
        return $documentPath;
    }

    public function assignAssetsWithLabel($data): Model
    {
        $toLabelData = $this->labelRepository->findById($data['label_id'], ['*'], []);

        foreach ($data['asset_id'] as $assetData) {

            $toLabelData->assets()->syncWithoutDetaching([$assetData]);
        }
        return $toLabelData;
    }

    /**
     * Chunk upload for asset
     * 
     * @return void
     */
    public function chunkAssetUpload($data, $assetId = null)
    {
        $file = $data->file('attachments');
        $mimeType = $data->file('attachments')->getMimeType();
        $identifier = $data->input('identifier');
        $chunkIndex = $data->input('chunk_index');
        $totalChunks = $data->input('total_chunks');
        $originalFilename = $data->input('filename');
        $fileExtension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $extension['extension'] = $fileExtension;
        $extensionResult = $this->assetExtensionRepository->firstOrCreate($extension);
        $assetSize = $data->input('asset_size');
        $filename = $data->input('filename');
        $fileExtension = pathinfo($filename, PATHINFO_EXTENSION);
        if ($chunkIndex == 0) {
            $assetData['filename']  = $originalFilename;
            $assetData['name']  = $originalFilename;
            $assetData['created_by']  = Auth::user()->id;
            $assetData['asset_url'] = 'temp';
            $assetData['extension'] = $fileExtension;
            $assetData['asset_key'] = substr(Str::random(20), 0, 14);
            $result = $this->assetRepository->create($assetData);
            $assetId = $result->id;
            $this->assetMeta($assetId, $file, $data->input('size'));
            if (! empty($data['workspace_id'])) {
                $result->workspaces()->attach($data['workspace_id']);
            }
            if (! empty($data['section_id'])) {
                $result->sections()->attach($data['section_id']);
            }
            if (! empty($data['subfolder_id'])) {
                $result->subfolders()->attach($data['subfolder_id']);
            }
            if (! empty($data['label_id'])) {
                $result->labels()->attach($data['label_id']);
            }
        }
        $tempDir = storage_path("app/public/uploads/temp/chunk/{$assetId}");
        if ($chunkIndex == 0 && file_exists($tempDir)) {
            $oldChunks = glob($tempDir . '/chunk_*');
            foreach ($oldChunks as $oldChunk) {
                @unlink($oldChunk);
            }
        }
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        // Store chunk
        $file->move($tempDir, "chunk_{$chunkIndex}");
        $re = ['is_completed' => false, 'ids' => $assetId, 'last_chunk_id' => $chunkIndex];


        // Check if all chunks are uploaded
        if ($chunkIndex + 1 == $totalChunks) {
            $fileExtension = pathinfo($originalFilename, PATHINFO_EXTENSION);
            $assetfileName = null;
            $assetfileName = Str::uuid() . '.' . $fileExtension;
            $finalPath = storage_path("app/public/attachments/" . $assetfileName);
            $finalFile = fopen($finalPath, 'ab');
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = $tempDir . "/chunk_{$i}";
                fwrite($finalFile, file_get_contents($chunkPath));
                unlink($chunkPath);
            }
            fclose($finalFile);
            rmdir($tempDir);
            $documentPath = config('deegest.asset_document.asset_file_path');
            $s3Path = $documentPath . $assetId . '/' . $originalFilename;
            /* store thumb file start */

            $ext = strtolower($fileExtension);
            $supported = [
                'jpg',
                'jpeg',
                'webp',
                'png',
                'gif',
                'pdf',
                'eps',
                'wbmp',
                'mp4'
            ];
            if (in_array($ext, $supported, true)) {
                $thumbnail = $this->assetRepository->assetImageResize($finalPath, $fileExtension);
                $docPath = config('deegest.asset_document.asset_file_path');
                $documentThumbPath = $this->getAssetThumFileName($docPath, $originalFilename);
                $documentFiles = ! empty($thumbnail) ? $this->assetRepository->thumbUploadMaster($thumbnail, $documentThumbPath) : '';
            }
            $documentFiles = !empty($finalPath) ? $this->assetRepository->uploadMaster($finalPath, $s3Path) : '';
            /* stroe thumb file end */

            $assetsData['asset_url'] = $s3Path;
            $assetsData['is_completed'] = (bool)true;
            $assetsData['url']             = $documentThumbPath ?? null;
            $assetsData['thumbnail_image'] = $documentThumbPath ?? null;
            $result = $this->assetRepository->update($assetId, $assetsData);
            unlink($finalPath);
            $re = ['is_completed' => true, 'ids' => $assetId, 'last_chunk_id' => $chunkIndex];
            return $re;
        }
        return $re;
    }

    public function getAssetThumFileName($documentPath, $originalFilename)
    {
        $timestamp = $originalFilename . time();
        $filename = "{$timestamp}.webp";
        $documentPath = $documentPath . $filename;
        return $documentPath;
    }

    /**
     * Find an Asset by their key.
     * @param int $AssetID The ID of the asset.
     * 
     */
    public function findByAssetKey(string $assetKey, object $request)
    {
        return $this->assetRepository->findByKey($assetKey);
    }

    /**
     * Find an Asset by their key.
     * @param int $AssetID The ID of the asset.
     * 
     */
    public function findByAsset($assetId)
    {
        return Assets::whereIn('id', $assetId)->pluck('asset_url')->toArray();
    }

    /**
     * Send notifications to users that are subscribed to the given collection.
     *
     * @param Collection $collection The collection to send notifications for.
     *
     * @return Collection A collection of sent notifications with their associated user.
     */
    public function notifyUserAssetUpdate($asset)
    {
        $user = User::find($asset->created_by);
        if (! $user) {
            return null;
        }

        // Send in-app notification if enabled
        $title = "Update Asset";
        $loginUser = Auth::user();
        $message = "{$loginUser->name} updated asset - {$asset->name}";
        $type = "asset_update";
        $asset = Assets::with('workspaces')->find($asset->id);
        $workspaceSlug = optional($asset->workspaces->first())->slug;
        $url = env('APP_FE_URL') . "workspace/{$workspaceSlug}?asset={$asset->asset_key}";
        // $url = json_encode($url, JSON_UNESCAPED_SLASHES);
        if ($user && !$user->hasRole('Super Admin')) {
            $this->assetRepository->notifyUser(
                $user,
                $title,
                $message,
                $url,
                $type,
                $loginUser->name,
                true,
                true,
                $asset->name
            );
        }
        $superadmins = User::whereHas('roles', function ($query) {
            $query->where('name', 'Super Admin');
        })->get();
        foreach ($superadmins as $superUser) {
            $this->assetRepository->notifyUser(
                $superUser,
                $title,
                $message,
                $url,
                $type,
                $loginUser->name,
                true,
                true,
                $asset->name
            );
        }
        return $asset;
    }
    public function notifyUserAssetDownload($asset, $request)
    {
        //$sharelinks = $asset->sharelinks()->get();
        $sharelink = ShareLinks::where('id', $request->input('sharelink_id'))->first();
        $emails = $sharelink->shareLinkLogs()->latest()->first()?->email;
        //foreach ($sharelinks as $sharelink) {
        $user = User::find($sharelink->create_by);
        if (! $user) {
            return null;
        }
        // Send in-app notification if enabled
        // if (!$sharelink->is_private) {
        //     $requestUser = $request->input('email');
        // } else {
        //     $requestUser = Auth::user()->email;
        // }
        $title = "Downloaded Asset from Sharelink";
        $requestUser = $emails;
        $message = "{$emails}  Downloaded {$asset->name} from Sharelink - {$sharelink->name}";
        $type = "sharelink_asset_download";
        $url = $sharelink->url;
        // $asset = array();
        $assetArray = [];
        $assetArray[] = $asset->name;
        // $url = json_encode($url, JSON_UNESCAPED_SLASHES);
        if (!($user && $user->hasRole('Super Admin'))) {
            if ($sharelink->is_notify) {
                $this->assetRepository->notifyUser(
                    $user,
                    $title,
                    $message,
                    $url,
                    $type,
                    $requestUser,
                    true,
                    true,
                    $sharelink->name,
                    $assetArray
                );
            }
        }

        $superadmins = User::whereHas('roles', function ($query) {
            $query->where('name', 'Super Admin');
        })->get();
        foreach ($superadmins as $superUser) {
            $this->assetRepository->notifyUser(
                $superUser,
                $title,
                $message,
                $url,
                $type,
                $requestUser,
                true,
                true,
                $sharelink->name,
                $assetArray
            );
        }
        //}
    }
    public function notifyUserAssetZipDownload($assetId, $request)
    {
        // $sharelinks = ShareLinks::whereHas('assets', function ($q) use ($assetId) {
        //     $q->whereIn('asset_id', $assetId);
        // })->get();
        $sharelink = ShareLinks::where('id', $request->input('sharelink_id'))->first();
        $emails = $sharelink->shareLinkLogs()->latest()->first()?->email;
        // $sharelinks = $assetId->sharelinks()->get();
        //  foreach ($sharelinks as $sharelink) {
        $user = User::find($sharelink->create_by);
        if (! $user) {
            return null;
        }
        // Send in-app notification if enabled
        if (!$sharelink->is_private) {
            $requestUser = $request->input('email');
        } else {
            $requestUser = Auth::user()->email;
        }
        $requestUser = $emails;
        $title = "Downloaded Asset from Sharelink";


        $type = "sharelink_asset_download";
        $url = $sharelink->url;
        $assetArray = [];
        $assetArray =  Assets::whereIn('id', $assetId)->pluck('name')->toArray();
        $assetsData = implode(", ", $assetArray);
        $message = "{$requestUser}  Downloaded {$assetsData} from Sharelink - {$sharelink->name}";
        if (!($user && $user->hasRole('Super Admin'))) {
            if ($sharelink->is_notify) {
                $this->assetRepository->notifyUser(
                    $user,
                    $title,
                    $message,
                    $url,
                    $type,
                    $requestUser,
                    true,
                    true,
                    $sharelink->name,
                    $assetArray
                );
            }
        }

        $superadmins = User::whereHas('roles', function ($query) {
            $query->where('name', 'Super Admin');
        })->get();
        foreach ($superadmins as $superUser) {
            $this->assetRepository->notifyUser(
                $superUser,
                $title,
                $message,
                $url,
                $type,
                $requestUser,
                true,
                true,
                $sharelink->name,
                $assetArray
            );
        }
        //}
    }
    /**
     * Create an audit log entry for a pivot data update.
     *
     * @param array $oldData The old data.
     * @param array $newData The new data.
     * @param Asset $asset The asset being updated.
     *
     * @return void
     */
    public function addPivotDataLog($oldData, $newData, $asset, $key)
    {
        $addedDatas = array_diff($newData, $oldData);
        $removedDatas = array_diff($oldData, $newData);
        if (!(empty($addedDatas) && empty($removedDatas))) {
            if (!empty($addedDatas)) {
                Audit::create([
                    'user_type' => get_class(Auth::user()),
                    'user_id' => Auth::user()->id,
                    'event' => 'updated',
                    'auditable_type' => get_class($asset),
                    'auditable_id' => $asset->id,
                    'old_values' => [],
                    'new_values' => [$key => $addedDatas],
                    'url' => request()->fullUrl(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'tags' => 'pivot-update',
                ]);
            }
            if (!empty($removedDatas)) {
                Audit::create([
                    'user_type' => get_class(Auth::user()),
                    'user_id' => Auth::user()->id,
                    'event' => 'updated',
                    'auditable_type' => get_class($asset),
                    'auditable_id' => $asset->id,
                    'old_values' => [],
                    'new_values' => [$key => $removedDatas],
                    'url' => request()->fullUrl(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'tags' => 'pivot-update',
                ]);
            }
        }
    }
    /**
     * Returns all the extensions of the assets
     * @return \Illuminate\Support\Collection
     */
    public function getExtension()
    {
        return $this->assetExtensionRepository->getExtension();
    }
}
