<?php

namespace App\Http\Requests\Auth;

use App\Models\InviteUsers;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use App\Models\User;
use App\Models\Role;
use App\Models\Workspaces;

/**
 * Class SignUpRequest
 *
 * Form request class for creating an new user.
 *
 * @package App\Http\Requests
 */
class OnBoardRequest extends FormRequest
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
            'name' => 'required|string|min:1|max:255',
            'email' => [
                'required',
                'email',
                'exists:invite_users,email',
                Rule::unique('users', 'email')->whereNull('deleted_at'),
            ],
            'password' => [
                'required',
                'string',
                Password::min(8)->mixedCase()->numbers()->symbols()->max(15),
            ]
        ];
    }
}
