<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Assets;

class AssetGuestResource extends JsonResource
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
        $sections = Assets::with('sections')->where('id', $this->id)->get();
        //$subfolders = Assets::with('subfolders')->where('id', $this->id)->get();
        //dd($subfolders);
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'asset_key' => $this->asset_key,
            'created_by' => User::find($this->created_by)?->name,
            'assets_url' => config('deegest.thumbnail_url').Storage::url('attachments/'.$this->asset_url),
            'filename' => $this->filename,
            'thumbnail_url' => isset($this->thumbnail_image) ? config('deegest.thumbnail_url').Storage::url($this->thumbnail_image)
            :'',
            'extension' => $this->extension,
            'publish_date' => $this->publish_date,
            'links' => $this->links,
            'rgb' => $this->rgb,
            'hex' => $this->hex,
            'cmyk' => $this->cmyk,
            'pantagone_coated' => $this->pantagone_coated,
            'pantagone_uncoated' => $this->pantagone_uncoated,
            'created_at' => $this->created_at->format('d/m/Y'),
            'updated_at' => $this->updated_at->format('d/m/Y'),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'metas' => AssetMetaResource::collection($this->whenLoaded('metas')),
            'labels' => LabelResource::collection($this->whenLoaded('labels')),                                     
            //'subfolders' => SubFolderResource::collection($subfolders),
            'sections' => SectionResource::collection($sections),       
        ];
    }
}
