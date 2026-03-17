<?php

namespace App\Http\Requests\Asset;

use App\Models\Assets;
use App\Models\Sections;
use App\Models\SubFolders;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class AssetThumbnailRequest extends FormRequest
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
            'thumbnail' => 'required',
            'thumbnail' => 'file|mimes:jpg,jpeg,png,webp,svg,jpeg|max:2048',
            "asset_id" => 'int|'.Rule::exists(Assets::class, 'id')->whereNull('deleted_at'),
        ];
    }
}
