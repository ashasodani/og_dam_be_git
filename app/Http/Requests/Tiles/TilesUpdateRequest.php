<?php

namespace App\Http\Requests\Tiles;

use App\Models\Tiles;
use App\Models\Portals;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class TilesUpdateRequest extends FormRequest
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
        $rule = Rule::unique(Tiles::class, "name")->ignore($this->id, "id")->whereNull("deleted_at");
        return [
            "name"        => "required|max:255|string|min:1|",
            "link_url" => "required|min:1|string",
            "description" => "nullable|string|max:255",
            "tile_type" => "required|in:navigation_tile,smartsheet_form",
            "position" => "required|integer",
            "portal_id" => 'required|int|'.Rule::exists(Portals::class, 'id')->whereNull('deleted_at'),
        ];
    }
      public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $tileId       = $this->id;
            $name        = $this->input('name');
            $name = strtolower($name);
            $portalId = $this->input('portal_id');

            if (! $tileId || ! $name || ! $portalId) {
                return;
            }

            $exists = DB::table('tiles')
                ->where('portal_id', $portalId)
                ->whereRaw('LOWER(tiles.name) = ?', [$name])
               // ->where('tags.name', $name)
                ->where('id', '!=', $tileId)
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                $validator->errors()->add('name', 'The Tile name already exists in this Portal.');
            }
        });
    }
}
