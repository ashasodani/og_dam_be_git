<?php
namespace App\Http\Requests\Asset;

use App\Models\Assets;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetUpdateRequest extends FormRequest
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
        $rule = Rule::unique(Assets::class, "slug")->ignore($this->route('asset'), "id")->whereNull("deleted_at");
        return [
            "links"            => "required_if:asset_type,press/links",
            "description"      => "nullable",
            "publish_date"     => "required_if:asset_type,press/links|max:255",
            'thumbnail'        => 'file|mimes:jpg,jpeg,png,webp|max:1048',
            'tags.*'           => 'required|string|max:255',
            'labels.*'         => 'required|string|max:255',
            'hex'              => 'required_if:asset_type,colors',
            'rgb'              => 'required_if:asset_type,colors',
            'cmyk'             => 'required_if:asset_type,colors',
            'pantagone_coated' => 'nullable',
            'pantagone_uncoated' => 'nullable',
        ];
    }
}
