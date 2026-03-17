<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InviteUserResource extends JsonResource
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
           'user_id' => $this->id,
           'status' => $this->status,
           'invitation_link' => $this->invite_link,
           'email_sent' => $this->email_sent,
           'status' => $this->status,
           'email' => $this->email,
           'invited_on' => $this->created_at->format('d/m/Y'),
           'workspaces_role' => [],
           'portals_role' => [],
        ];
    }
}
