<?php

namespace App\Http\Requests\Tiles;

use App\Models\Tiles;
use App\Models\Portals;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class TilesCreateRequest extends FormRequest
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
            "name"       => "required|string|min:1|max:255|",
            "tile_type" => "required|in:navigation_tile,smartsheet_form",
            "description" => "nullable|string|max:255",
            "position" => "required|integer",
            "link_url" => "required|min:1|string",
            "tile_image" => "required",
            "tile_image" => "file|mimes:jpg,jpeg,png,webp,svg|max:2048",
            "grid_size" => "required|string",
            "portal_id" => 'required|int|'.Rule::exists(Portals::class, 'id')->whereNull('deleted_at'),
        ];
    }
       public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $portalId = $this->input('portal_id');
            $names       = $this->input('names');

            if (! $portalId || ! is_array($names)) {
                return;
            }

            foreach ($names as $index => $name) {
                $name = strtolower($name);
                $exists = DB::table('tiles')
                    ->where('portal_id', $portalId)
                    ->whereRaw('LOWER(name) = ?', [$name])
                    ->whereNull('deleted_at')
                    ->exists();

                if ($exists) {
                    $validator->errors()->add("names.$index", "The tile name \"$name\" already exists in this workspace.");
                }
            }
        });
    }

      /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'tile_image.required' => 'Please upload an image.',
            'tile_image.mimes' => 'Only JPEG, PNG, JPG, and SVG, WEBP formats are allowed.',
            'tile_image.max' => 'The image size must not exceed 2MB.',
            'tile_image.uploaded'    => 'Ensure thumbnail image is below 2MB and of a valid format.',
        ];
    }
}
