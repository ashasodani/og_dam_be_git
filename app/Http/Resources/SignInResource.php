<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;
use App\Models\Role; 

class SignInResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $token = $this->createToken($this->name)->plainTextToken;
        $user = User::find($this->id);
        $workspaces = [];
        $portals = [];

        return [
            'access_token' => $token,
            'user' => [
                'id'            => $this->id,
                'first_name'    => $this->name,
                'email'         => $this->email,
                'last_login'         => $this->last_login,
                'role'          => $user->getRoleNames(),
                'permission' => $user->getAllPermissions()->pluck('name'),
                'workspaces_role'   => [],
                'workspaces_portal' => [],
            ]
        ];
    }
}
