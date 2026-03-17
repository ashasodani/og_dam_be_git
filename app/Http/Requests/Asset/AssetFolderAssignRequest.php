<?php

namespace App\Http\Requests\Asset;

use App\Models\Assets;
use App\Models\Sections;
use App\Models\SubFolders;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class AssetFolderAssignRequest extends FormRequest
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
            'assests' => 'array',
            'assests.*' => 'int|'.Rule::exists(Assets::class, 'id'),
            "subfolder_id" => "required_if:type,existing|int|".Rule::exists(SubFolders::class, 'id')->whereNull('deleted_at'),
            "subfolder_name"       => "required_if:type,new|min:1|string|max:255",
            "section_id" => "required_if:type,new|int|".Rule::exists(Sections::class, 'id')->whereNull('deleted_at'),
            "type" => "required|in:new,existing",
        ];
    }
}
