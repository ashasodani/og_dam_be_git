<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
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
            'id'         => $this->id,
            'city_name'       => $this->port_name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // 'assets'     => AssetResource::collection($this->whenLoaded('assets')),
            // 'assets_count'=> $this->assets()->where('is_completed',true)->count(),                  
            // 'workspaces' => WorkspaceResource::collection($this->whenLoaded('workspaces')),
        ];
    }
}
