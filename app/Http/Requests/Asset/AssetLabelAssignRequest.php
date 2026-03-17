<?php
namespace App\Http\Requests\Asset;

use App\Models\Assets;
use App\Models\Labels;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetLabelAssignRequest extends FormRequest
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
            'assets'   => 'array',
            'assets.*' => 'int|' . Rule::exists(Assets::class, 'id'),
            "label_id" => 'required|int|' . Rule::exists(Labels::class, 'id')->whereNull('deleted_at'),
        ];
    }
}
