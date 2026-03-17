<?php

namespace App\Http\Requests\Portal;

use App\Models\ExternalLinks;
use App\Models\Portals;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class RelatedPortalCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'related_portal_id' => 'array',
            "related_portal_id.*" => 'int|'.Rule::exists(Portals::class, 'id')->whereNull('deleted_at'),
            "portal_id" => 'required|int|'.Rule::exists(Portals::class, 'id')->whereNull('deleted_at'),
        ];
    }
}
