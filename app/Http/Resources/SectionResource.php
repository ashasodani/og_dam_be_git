<?php
namespace App\Http\Resources;

use App\Http\Resources\AssetResource;
use App\Http\Resources\CollectionResource;
use App\Http\Resources\ShareLinkResource;
use App\Http\Resources\SubFolderResource;
use App\Http\Resources\WorkspaceResource;
use App\Models\Assets;
use App\Models\SubFolders;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function __construct(
        public $resource
    ) {
        $this->resource = $resource;
    }
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $assetDBCount = Assets::whereHas('sections', function ($q) {
                $q->where('section_id', $this->id)
                    ->whereNull('section_assets.deleted_at');
            })->whereNull('assets.deleted_at')->where('is_completed',true)->count();
             $subfolderDBCount = Subfolders::whereHas('sections', function ($q) {
                $q->where('section_id', $this->id)
                    ->whereNull('sections_subfolder.deleted_at');
            })->count();
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'slug'              => $this->slug,
            'position'          => $this->position,
            'description'       => $this->description,
            'type'              => $this->asset_type,
            'privacy'           => $this->privacy,
            'asset_filter_count'=>$this->filtered_assets_count??$assetDBCount,
            'assets_count'      => $this->filtered_section_assets_count??$assetDBCount,
            'assets_count_bkp'      => Assets::whereHas('sections', function ($q) {
                $q->where('section_id', $this->id)
                    ->whereNull('section_assets.deleted_at');
            })->whereNull('assets.deleted_at')->where('is_completed',true)->count(),

            'sub_folders_count' => $this->filtered_section_folder_count??$subfolderDBCount,

            'created_at'        => $this->created_at->format('d/m/Y'),
            'updated_at'        => $this->updated_at->format('d/m/Y'),
            'workspaces'        => WorkspaceResource::collection($this->whenLoaded('workspaces')),
            'collections'       => CollectionResource::collection($this->whenLoaded('collections')),
            'subfolders'        => SubFolderResource::collection($this->whenLoaded('subfolders')),
            'assets'            => AssetResource::collection($this->whenLoaded('assets')),
            'sharelinks'        => ShareLinkResource::collection($this->whenLoaded('sharelinks')),
        ];
    }
}
