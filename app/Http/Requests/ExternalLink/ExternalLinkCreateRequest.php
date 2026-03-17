<?php

namespace App\Http\Requests\ExternalLink;

use App\Models\ExternalLinks;
use App\Models\Portals;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ExternalLinkCreateRequest extends FormRequest
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
            "name" => "required|min:1|string|max:255",
            "link_url" => "required|min:1|string",
            'link_icon' => 'file|mimes:jpg,jpeg,png,webp,svg|max:1048',
            "portal_id" => 'required|int|'.Rule::exists(Portals::class, 'id')->whereNull('deleted_at'),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'link_icon.mimes' => 'Only JPEG, PNG, JPG, and SVG, WEBP formats are allowed.',
            'link_icon.max' => 'The image size must not exceed 2MB.',
        ];
    }
}
