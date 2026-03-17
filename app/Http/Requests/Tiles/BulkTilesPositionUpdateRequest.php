<?php

namespace App\Http\Requests\Tiles;

use Illuminate\Foundation\Http\FormRequest;

class BulkTilesPositionUpdateRequest extends FormRequest
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
            'tiles' => 'required|array|min:1',
            'tiles.*.id' => 'required|integer|exists:tiles,id',
            'tiles.*.position' => 'required|integer|min:0',
        ];
    }
}
