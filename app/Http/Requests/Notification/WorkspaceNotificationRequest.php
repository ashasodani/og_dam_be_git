<?php
namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

class WorkspaceNotificationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'workspace_id'  => 'required|integer|exists:workspaces,id',
            'collection_id' => 'required|integer|exists:collections,id',
            'is_mail'       => 'required|boolean',
            'in_app'        => 'required|boolean',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
