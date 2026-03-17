<?php

namespace App\Http\Requests\Asset;

use App\Models\Assets;
use App\Models\Sections;
use App\Models\SubFolders;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class AssetTypeCreateRequest extends FormRequest
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
            "section_id" => 'int|required|'.Rule::exists(Sections::class, 'id')->whereNull('deleted_at'),
            "subfolder_id" => 'int|'.Rule::exists(SubFolders::class, 'id')->whereNull('deleted_at'),
            "name" => "required|min:1|string|max:255",
            "links" => "required_if:asset_type,press/links|min:1|string",
            "description" => "nullable",
            "publish_date" => "string|required_if:asset_type,press/links|min:1|max:255",
            'thumbnail' => 'required',
            'thumbnail' => 'file|mimes:jpg,jpeg,png,webp,svg,jpeg|max:2048',
            'asset_image' => 'required',
            'asset_image' => 'max:2048',
            'tags.*' => 'required|string|max:255',
            'labels.*' => 'required|string|max:255',
            'asset_type' => 'required',
            'hex' => 'required_if:asset_type,colors',
            'rgb' => 'required_if:asset_type,colors',
            'cmyk' => 'required_if:asset_type,colors',
            'pantagone_coated' => 'nullable',
            'pantagone_uncoated' => 'nullable',
        ];
    }
}
