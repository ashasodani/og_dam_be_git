<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Class ChangePasswordRequest
 *
 * Form request class for changing the user password.
 *
 * @package App\Http\Requests
 */
class ChangePasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True if the user is authorized, false otherwise.
     */
    public function authorize()
    {
        return true; // Usually handled in your specific authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> The validation rules.
     */
    public function rules(): array
    {
        return [
            'old_password' => 'required',
            'new_password' => [
                'required',
                'string',
                Password::min(8)->mixedCase()->numbers()->symbols()->max(15),
            ],
            'confirm_password' => 'required|required_with:new_password|same:new_password',
        ];
    }
}
