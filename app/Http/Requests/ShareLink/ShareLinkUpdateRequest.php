<?php
namespace App\Http\Requests\ShareLink;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Http\FormRequest;

class ShareLinkUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'             => 'sometimes|required|string|min:1|max:255',
            'url'              => 'nullable|string|max:2048',
            'is_private'       => 'sometimes|boolean',
            'is_email_address' => 'sometimes|boolean',
            'email'            => 'nullable|string|email|max:255',
            'is_password'      => 'sometimes|required|boolean',
            'is_notify'        => 'sometimes|boolean',
            's_password'       => [
                'nullable',
                Rule::requiredIf($this->input('is_password') === true),
                'string',
                'min:6',
                'max:255',
            ],
            'is_expired'       => 'sometimes|required|boolean',
            'expiry_date'      => [
                'nullable',
                Rule::requiredIf($this->input('is_expired') === true),
                'date',
            ],
            'timezone'         => 'nullable|string|max:100',
            'status'           => ['sometimes', 'required', Rule::in([0, 1])],
            "days" => "nullable",
            'context_type'     => ['sometimes', 'required', Rule::in(['brand-folder', 'collection', 'organization'])],
        ];
    }

    // public function withValidator($validator)
    // {
    //     $validator->after(function ($validator) {
    //         $workspaceId = $this->input('workspace_id');
    //         $url         = $this->input('url');
    //         $shareLink   = $this->route('sharelink'); // Could be model or ID
    //         $shareLinkId = is_object($shareLink) ? $shareLink->id : $shareLink;

    //         if (! $workspaceId || ! $url) {
    //             return;
    //         }

    //         $exists = DB::table('share_links')
    //             ->join('share_links_workspace', 'share_links.id', '=', 'share_links_workspace.share_link_id')
    //             ->where('share_links_workspace.workspace_id', $workspaceId)
    //             ->where('share_links.url', $url)
    //             ->whereNull('share_links.deleted_at')
    //             ->where('share_links.id', '!=', $shareLinkId)
    //             ->exists();

    //         if ($exists) {
    //             $validator->errors()->add('url', 'The URL has already been taken in this workspace.');
    //         }
    //     });
    // }
}
