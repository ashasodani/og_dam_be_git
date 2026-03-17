<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TilesResource extends JsonResource
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
            'position'    => $this->position,
            'link_url'    => $this->link_url,
            'description' => $this->description,
            'tile_type'   => $this->tile_type,
            'tile_image'  => $this->tile_image,
            'grid_size'   => $this->grid_size,
            'tile_url' => (isset($this->tile_url) )
                        ? Storage::disk('s3')->temporaryUrl(
                            $this->tile_url,
                            now()->addMinutes(30),
                            ['ResponseContentDisposition' => 'inline']
                        )
            : 'https://picsum.photos/200/300?random=3',
            'created_at'  => $this->created_at ? $this->created_at->format('d/m/Y') : null,
            'updated_at'  => $this->updated_at ? $this->updated_at->format('d/m/Y') : null,
        ];
    }
}
