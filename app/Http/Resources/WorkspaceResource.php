<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class WorkspaceResource extends JsonResource
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
            'slug' => $this->slug,
            'description' => $this->description,
            'privacy' => $this->privacy,
            'created_at' => $this->created_at->format('d/m/Y'),
            'updated_at' => $this->updated_at->format('d/m/Y'),
            'thumbnail_image' => $this->thumbnail_image,
            'url' => (isset($this->url) && Storage::disk('s3')->exists($this->url))
                        ? Storage::disk('s3')->temporaryUrl(
                            $this->url,
                            now()->addMinutes(30),
                            ['ResponseContentDisposition' => 'inline']
                        )
            : 'https://picsum.photos/200/300?random=3',
            //'url' => isset($this->url) ? config('deegest.s3_bucket_url').$this->url
           // :'https://picsum.photos/200/300?random=3',
            'sections' => SectionResource::collection($this->whenLoaded('sections')),
            'collections' => CollectionResource::collection($this->whenLoaded('collections')),   
            'labels' => LabelResource::collection($this->whenLoaded('labels')), 
            'users_count' => $this->users->count(),                                
        ];
    }
}
