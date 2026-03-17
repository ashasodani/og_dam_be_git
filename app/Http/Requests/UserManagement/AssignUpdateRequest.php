<?php

namespace App\Http\Requests\UserManagement;

use App\Models\Workspaces;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class AssignUpdateRequest extends FormRequest
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
        $rule = Rule::unique(User::class, "name")->ignore($this->id, "id")->whereNull("deleted_at");
        return [
            "name" => "required|max:255|string|min:1",
            'collection_data' => ['required', 'array'],
            'collection_data.*.resource_id' => ['required', 'integer'],
            'collection_data.*.role_id' => ['required', 'integer', 'exists:roles,id'],
            'collection_data.*.type' => 'required|in:workspace,portal',
        ];
    }
}
