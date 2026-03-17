<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ForgotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'email'      => $this->email,
            'token'      => $this->token,
            'reset_link' => config('deegest.reset_url') . '?token='. $this->token .'&email=' . urlencode($this->email),
        ];
    }
}
