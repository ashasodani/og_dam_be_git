<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class AssetLogResource extends JsonResource
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
            'id' => $this->auditable_id,
            'action' => $this->event,
            'edit_by' => $this->user_id,
            'edit_by_name' => User::find($this->user_id)?->name,
            'old_value' => $this->old_values,
            'new_value' => $this->new_values,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
                    
        ];
    }
}
