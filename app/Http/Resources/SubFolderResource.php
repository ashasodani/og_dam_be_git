<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubFolderResource extends JsonResource
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
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'created_at'  => $this->created_at->format('d/m/Y'),
            'updated_at'  => $this->updated_at->format('d/m/Y'),
            'assets_count_bkp'=> $this->assets()->where('is_completed',true)->count(), 
            'assets_count'=> $this->filtered_subfolder_assets_count??0 ,
            'sections'    => SectionResource::collection($this->sections),
            'assets'    => AssetResource::collection($this->whenLoaded('assets')),
            'workspaces'    => WorkspaceResource::collection($this->workspaces),
            
        ];
    }
}
