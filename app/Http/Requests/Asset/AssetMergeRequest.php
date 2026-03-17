<?php

namespace App\Http\Requests\Asset;

use App\Models\Assets;
use App\Models\Sections;
use App\Models\SubFolders;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class AssetMergeRequest extends FormRequest
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
            "from_section_id" => 'required|int|'.Rule::exists(Sections::class, 'id')->whereNull('deleted_at'),
            "to_section_id" => 'required|int|'.Rule::exists(Sections::class, 'id')->whereNull('deleted_at'),
            "from_subfolder_id" => 'int|'.Rule::exists(SubFolders::class, 'id')->whereNull('deleted_at'),
            "to_subfolder_id" => 'int|'.Rule::exists(SubFolders::class, 'id')->whereNull('deleted_at'),
        ];
    }
}
