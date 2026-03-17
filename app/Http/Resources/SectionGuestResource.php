<?php
namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectionGuestResource extends JsonResource
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
        'id' => $this->id,
        'name' => $this->name,
        'type' => $this->asset_type,
        'assets' => AssetManagementResource::collection($this->assets),
        'folders' => $this->subfolders->map(function ($folder) {
            return [
                'id' => $folder->id,
                'name' => $folder->name,
                'assets_count' => $folder->assets->count(),
                'assets' => AssetManagementResource::collection($folder->assets),
            ];
        }),
    ];
    }
}
