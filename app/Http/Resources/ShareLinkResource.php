<?php
namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ShareLinkResource extends JsonResource
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
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->hasRole('Super Admin') || $user->id === $this->create_by) {
                $isPassword = 'No';
            } else {
                $isPassword = $this->is_password ? 'Yes' : 'No';
            }
        } else {
            $isPassword = $this->is_password ? 'Yes' : 'No';
        }
       if($this->expiry_date){
        $utcDateTime = $this->expiry_date??null;
        $utc = Carbon::createFromFormat('Y-m-d H:i:s', $utcDateTime, 'UTC')??null;
        $est = $utc->copy()->setTimezone($this->timezone)??null;
        $convert = $est->format('d/m/Y');
        
       }
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'type'             => $this->asset_type,
            'url'              => $this->url,
            'is_private'       => $this->is_private ? 'Yes' : 'No',
            'is_email_address' => $this->is_email_address ? 'Yes' : 'No',
            'is_password'      => $isPassword,
            'is_expired'       => $this->is_expired ? 'Yes' : 'No',
            'expiry_date'      => $this->expiry_date ? $convert : null,
            'timezone'         => $this->timezone,
            'create_by'        => $this->createdBy->email ?? null,
            'view_count'       => $this->share_link_logs_count,
            'days'             => $this->days,
            'status'           => $this->status == 1 ? 'Active' : 'Expired',
            'is_notify'       => $this->is_notify ? 'Yes' : 'No',
            'context_type'     => $this->context_type,
            'assets_count'      => $this->assets_count,                 
            'created_at'       => $this->created_at ? $this->created_at->format('d/m/Y H:i') : null,
            'updated_at'       => $this->updated_at ? $this->updated_at->format('d/m/Y H:i') : null,
            'assets'           => AssetGuestResource::collection($this->whenLoaded('assets')),
        ];
    }
}
