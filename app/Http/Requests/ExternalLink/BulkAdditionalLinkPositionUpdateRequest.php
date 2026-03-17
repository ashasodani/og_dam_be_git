<?php
namespace App\Http\Requests\ExternalLink;

use Illuminate\Foundation\Http\FormRequest;

class BulkAdditionalLinkPositionUpdateRequest extends FormRequest
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
            'additional_links'            => 'required|array|min:1',
            'additional_links.*.id'       => 'required|integer|exists:additional_links,id',
            'additional_links.*.position' => 'required|integer|min:0',
        ];
    }
}
