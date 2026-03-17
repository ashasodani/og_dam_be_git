<?php
namespace App\Http\Requests\Asset;

use Illuminate\Foundation\Http\FormRequest;

class AssignLabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_id'   => 'required|array',
            'asset_id.*' => 'exists:assets,id',
            'label_id'   => 'required|array',
            'label_id.*' => 'exists:labels,id',
        ];
    }
}
