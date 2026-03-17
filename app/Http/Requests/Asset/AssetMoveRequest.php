<?php
namespace App\Http\Requests\Asset;

use App\Models\Sections;
use App\Models\SubFolders;
use App\Rules\IdOrArrayRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetMoveRequest extends FormRequest
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
            'asset_id'          => ['array','nullable'],
            'asset_id.*'        => [
                'integer',
                Rule::exists((new \App\Models\Assets)->getTable(), 'id')->whereNull('deleted_at'),
            ],
            'subfolder_id'          => ['array','nullable'],
            'subfolder_id.*'        => [
                'integer',
                Rule::exists((new \App\Models\SubFolders())->getTable(), 'id')->whereNull('deleted_at'),
            ],

            'from_section_id'   => ['nullable', new IdOrArrayRule((new Sections)->getTable())],
            'to_section_id'     => ['nullable', new IdOrArrayRule((new Sections)->getTable()), 'required_without:to_subfolder_id'],

            'from_subfolder_id' => ['nullable', new IdOrArrayRule((new SubFolders)->getTable())],
            'to_subfolder_id'   => ['nullable', new IdOrArrayRule((new SubFolders)->getTable()), 'required_without:to_section_id'],
        ];
    }
}
