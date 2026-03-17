<?php
namespace App\Http\Resources;

use App\Models\Labels;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LabelResource extends JsonResource
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
            'name'       => $this->name,
            'parent_key' => Labels::where('id', $this->parent_key)->value('name')??null,
            'parent_id'  => $this->parent_key,
            'created_at' => $this->created_at->format('d/m/Y'),
            'updated_at' => $this->updated_at->format('d/m/Y'),
            'workspaces' => WorkspaceResource::collection($this->whenLoaded('workspaces')),
            'assets'   => AssetResource::collection($this->whenLoaded('assets')),
        ];
    }
}
