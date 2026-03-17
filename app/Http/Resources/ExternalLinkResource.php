<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ExternalLinkResource extends JsonResource
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
        if($this->link_icon){
            $linkUrl =  config('deegest.thumbnail_url').Storage::url($this->link_icon);
        } else {
            $linkUrl = null;
        }
        return [
            'id' => $this->id,
            'name' => $this->name,
            'link_url' => $this->link_url,
            'link_icon' => (isset($this->link_icon))
                        ? Storage::disk('s3')->temporaryUrl(
                            $this->link_icon,
                            now()->addMinutes(30),
                            ['ResponseContentDisposition' => 'inline']
                        )
            : 'https://picsum.photos/200/300?random=3',
            //'link_icon'    => isset($this->link_icon) ? config('deegest.s3_bucket_url') . $this->link_icon : 'https://picsum.photos/200/300?random=3',
            'is_link_icon' => isset($this->link_icon) ? true:false,
            'created_at' => $this->created_at ? $this->created_at->format('d/m/Y') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('d/m/Y') : null,
            'tiles' => TilesResource::collection($this->whenLoaded('tiles')),
            "position"=>$this->position
        ];
    }
}
