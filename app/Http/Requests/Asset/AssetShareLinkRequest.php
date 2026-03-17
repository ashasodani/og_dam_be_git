<?php

namespace App\Http\Requests\Asset;

use App\Models\Assets;
use App\Models\ShareLinks;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class AssetShareLinkRequest extends FormRequest
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
            'asset_id'         => 'array',
            'asset_id.*'       => 'integer|exists:assets,id',
            'section_id'         => 'array',
            'section_id.*'       => 'integer|exists:sections,id',
            'subfolder_id'         => 'array',
            'subfolder_id.*'       => 'integer|exists:sub_folders,id',
            'sharelink_id' => 'array',
            'sharelink_id.*' => 'int|'.Rule::exists(ShareLinks::class, 'id'),
        ];
    }
}
