<?php
namespace App\Http\Requests\Collection;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CollectionRemoveRequest extends FormRequest
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
        ];
    }
}
