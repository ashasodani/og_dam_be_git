<?php
namespace App\Http\Requests\Asset;

use Illuminate\Foundation\Http\FormRequest;

class RemoveFromLabelsRequest extends FormRequest
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
        ];
    }
}
