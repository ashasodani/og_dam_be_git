<?php

namespace App\Http\Requests\Invite;

use App\Models\Workspaces;
use App\Models\InviteUsers;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ResendInviteRequest extends FormRequest
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
            'email' => [
                'required',
                'email',
                Rule::exists('invite_users', 'email')->whereNull('deleted_at'),
            ],
        ];
    }
}
