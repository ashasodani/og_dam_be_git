<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class AssetResource extends JsonResource
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
        $inSection = $this->sections->isNotEmpty();
        $inSubfolder = $this->subfolders->isNotEmpty();
        if ($inSection && $inSubfolder) {
           $copied = true;
        } else {
            $copied = false;
        }
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'asset_key' => $this->asset_key,
            'created_by' => User::find($this->created_by)?->name,
            'filename' => $this->filename,
            'extension' => $this->extension,
            'publish_date' => $this->publish_date,
            'links' => $this->links,
            'rgb' => $this->rgb,
            'hex' => $this->hex,
            'cmyk' => $this->cmyk,
            'pantagone_coated' => $this->pantagone_coated,
            'pantagone_uncoated' => $this->pantagone_uncoated,
            'is_thumbnail' => isset($this->url) ? true:false,
            'created_at' => $this->created_at->format('d/m/Y'),
            'updated_at' => $this->updated_at->format('d/m/Y'),
            'tags' => TagResource::collection($this->tags),
            'metas' => AssetMetaResource::collection($this->metas),
            'has_subfolders' => $this->subfolders->isNotEmpty(),
            'has_copied' => $copied,
            'labels' => LabelResource::collection($this->labels),
            'workspace' => WorkspaceResource::collection($this->workspaces),
            'sharelinks'    => ShareLinkResource::collection($this->sharelinks),                                     
            'subfolders' => SubFolderResource::collection($this->whenLoaded('subfolders')),
            'sections' => SectionResource::collection($this->sections),          
            'collections' => CollectionResource::collection($this->collections),         
        ];
    }
}
