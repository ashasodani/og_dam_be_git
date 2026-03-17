<?php

namespace App\Http\Requests\UserManagement;

use App\Models\Workspaces;
use App\Models\Users;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class AssignCreateRequest extends FormRequest
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
            'userdata.*.email' => [
                'required',
                'email',
                Rule::exists('users', 'email')->whereNull('deleted_at'),
            ],
            'userdata' => ['required', 'array'],
            'users.*.collection_data' => ['required', 'array'],
            'users.*.collection_data.*.resource_id' => ['required', 'integer', 'exists:workspaces,id |exists:portals,id'],
            'users.*.collection_data.*.role' => ['required', 'string', 'exists:roles,id'],
            'users.*.collection_data.*.type' => ['required|in:workspaces,portal'],
            "personal_message" => "string|required|min:1|max:255",
        ];
    }
}
