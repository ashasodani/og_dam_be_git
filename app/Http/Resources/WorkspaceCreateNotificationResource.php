<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkspaceCreateNotificationResource extends JsonResource
{
    public function __construct(public $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'workspace_id'  => $this->workspace_id,
            'collection_id' => $this->collection_id,
            'is_mail'       => (bool) $this->is_mail,
            'in_app'        => (bool) $this->in_app,
        ];
    }
}
