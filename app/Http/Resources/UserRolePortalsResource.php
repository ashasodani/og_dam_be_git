<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Role; 

class UserRolePortalsResource extends JsonResource
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
        $role = Role::find($this->pivot->role_id);
        $roleName = $role?->name;
        return [
            'portal_id' => $this->pivot->portal_id,
            'portal_name' => $this->name,
            'role_id' => $this->pivot->role_id,
            'role_name' => $roleName,
            'invited_user_id' => $this->pivot->user_id,                             
        ];
    }
}
