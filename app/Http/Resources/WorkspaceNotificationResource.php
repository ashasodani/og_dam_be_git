<?php
namespace App\Http\Resources;

use App\Models\WorkspaceNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkspaceNotificationResource extends JsonResource
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
            'id'                     => $this['workspace_id'],
            'name'                   => $this['workspace_name'],
            'workspace_collections' => $this['collections']
        ];
    }
}
