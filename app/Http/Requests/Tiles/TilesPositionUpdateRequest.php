<?php
namespace App\Http\Requests\Tiles;

use App\Models\Portals;
use App\Models\Tiles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TilesPositionUpdateRequest extends FormRequest
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
    {     $rule = Rule::unique(Tiles::class, "position")->ignore($this->id, "id")->whereNull("deleted_at");
        return [
            "position"    => "required|integer"
        ];
    }
}
