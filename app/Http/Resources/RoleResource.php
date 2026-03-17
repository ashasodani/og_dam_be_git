<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Role;

class RoleResource extends JsonResource
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
        if($this->resource) {
            $rolesWithUserCount = Role::findOrFail($this->id)->users()->count();
            return [
                'id' => $this->id,
                'name' => $this->name,
                'users' => $rolesWithUserCount,
                'created_at' => $this->created_at->format('d/m/Y'),
                'updated_at' => $this->updated_at->format('d/m/Y'),
            ];
        } else {
            return [];
        }
        
    }
}
