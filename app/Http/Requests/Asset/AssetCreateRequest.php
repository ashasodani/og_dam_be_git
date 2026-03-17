<?php

namespace App\Http\Requests\Asset;

use App\Models\Assets;
use App\Models\Sections;
use App\Models\SubFolders;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class AssetCreateRequest extends FormRequest
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
            'attachments' => 'required|array',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf|max:2048',
            "section_id" => 'int|'.Rule::exists(Sections::class, 'id')->whereNull('deleted_at'),
            "name" => "required|min:1|string|max:255",
            "description" => "nullable|string|max:255",
            "subfolder_id" => 'int|'.Rule::exists(SubFolders::class, 'id')->whereNull('deleted_at'),
        ];
    }
}
