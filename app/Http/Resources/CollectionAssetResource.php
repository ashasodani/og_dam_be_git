<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionAssetResource extends JsonResource
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
            'privacy'     => $this->privacy,
            'description' => $this->description,
            'created_at'  => $this->created_at->format('d/m/Y'),
            'updated_at'  => $this->updated_at->format('d/m/Y'),
            'section_count' => $this->count(),
            'workspace_slug' => $this->workspaces->value('slug'),
            'workspace_id' =>$this->workspaces->value('id'),
            'workspaces' => WorkspaceResource::collection($this->whenLoaded('workspaces')),  
            'sections' => SectionResource::collection($this->whenLoaded('sections')),  
            'assets' => AssetGuestResource::collection($this->whenLoaded('assets')),  
           'assets_count_bkp'=> $this->assets()->where('is_completed',true)->count(),  
            'assets_count'=> $this->filter_section_count??0,
           'sections'           => SectionGuestResource::collection($this->selectedData),                                
        ];
    }
}
