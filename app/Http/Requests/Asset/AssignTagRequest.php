<?php
namespace App\Http\Requests\Asset;

use Illuminate\Foundation\Http\FormRequest;

class AssignTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assets'                 => 'required|array',
            'assets.*.asset_id'      => 'array',
            'assets.*.asset_id'      => 'required|exists:assets,id',
            'assets.*.add_tags'      => 'array',
            'assets.*.add_tags.*'    => 'integer|exists:tags,id',
            'assets.*.remove_tags'   => 'array',
            'assets.*.remove_tags.*' => 'integer|exists:tags,id',
        ];
    }
}
