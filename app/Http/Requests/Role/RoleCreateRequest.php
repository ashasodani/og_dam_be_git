<?php

namespace App\Http\Requests\Role;

use App\Models\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class RoleCreateRequest
 *
 * Form request class for creating the roles.
 *
 * @package App\Http\Requests
 */
class RoleCreateRequest extends FormRequest
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
            'name' => 'required|string|min:1|max:255|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'int|'.Rule::exists(Permission::class, 'id'),
        ];
    }
}
