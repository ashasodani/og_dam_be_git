<?php
namespace App\Http\Requests\Section;

use Illuminate\Foundation\Http\FormRequest;

class BulkSectionsPositionUpdateRequest extends FormRequest
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
            'sections'            => 'required|array|min:1',
            'sections.*.id'       => 'required|integer|exists:sections,id',
            'sections.*.position' => 'required|integer|min:0',
        ];
    }
}
