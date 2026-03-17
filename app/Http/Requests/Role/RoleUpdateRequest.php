<?php

namespace App\Http\Requests\Role;

use App\Models\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class RoleUpdateRequest
 *
 * Form request class for updating a roles.
 *
 * @package App\Http\Requests
 */
class RoleUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True if the user is authorized, false otherwise.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> The validation rules.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:1|max:255',
            'permissions' => 'array',
            'permissions.*' => 'int|'.Rule::exists(Permission::class, 'id'),
        ];
    }
}
