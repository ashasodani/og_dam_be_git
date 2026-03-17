<?php
namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SharelinkLogResource extends JsonResource
{
    public function __construct(public $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'share_link_id' => $this->share_link_id,
            'email'         => $this->email,
            'ip_address'    => $this->ip_address,
            'user_agent'    => $this->user_agent,
            'is_login_user' => $this->is_login_user ? 'Yes' : 'No',
            'created_at'    => $this->created_at ? Carbon::parse($this->created_at)->format('d/m/Y H:i') : null,
            'updated_at'    => $this->updated_at ? Carbon::parse($this->updated_at)->format('d/m/Y H:i') : null,
        ];
    }
}
