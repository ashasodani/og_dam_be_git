<?php
namespace App\Http\Requests\ShareLink;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ShareLinkCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'             => 'required|string|min:1|max:255',
            'asset_id'         => 'array',
            'asset_id.*'       => 'integer|exists:assets,id',
            'section_id'       => 'array',
            'section_id.*'     => 'integer|exists:sections,id',
            'subfolder_id'     => 'array',
            'subfolder_id.*'   => 'integer|exists:sub_folders,id',
            'workspace_id'     => 'required|integer|exists:workspaces,id',
            'url'              => 'nullable|string|max:2048',
            'is_private'       => 'sometimes|boolean',
            'is_notify'        => 'sometimes|boolean',
            'is_email_address' => 'required_without:is_password|boolean',
            'email'            => 'nullable|string|email|max:255',
            'is_password'      => 'required_without:is_email_address|boolean',
            's_password'       => [
                'nullable',
                Rule::requiredIf($this->input('is_password') === true),
                'string',
                'min:6',
                'max:255',
            ],
            'is_expired'       => 'required|boolean',
            'expiry_date'      => [
                'nullable',
                Rule::requiredIf($this->input('is_expired') === true),
                'date',
            ],
            'timezone'         => 'nullable|string|max:100',
            "days" => "nullable",
            'status'           => ['required', Rule::in([0, 1])],
            'context_type'     => ['required', Rule::in(['brand-folder', 'collection', 'organization'])],
        ];
    }

    // public function withValidator($validator)
    // {
    //     $validator->after(function ($validator) {
    //         $workspaceId = $this->input('workspace_id');
    //         $url         = $this->input('url');
    //         $shareLink   = $this->route('share_link'); // Might be model or ID
    //         $shareLinkId = is_object($shareLink) ? $shareLink->id : $shareLink;

    //         if (! $workspaceId || ! $url) {
    //             return;
    //         }

    //         $exists = DB::table('share_links')
    //             ->join('share_links_workspace', 'share_links.id', '=', 'share_links_workspace.share_link_id')
    //             ->where('share_links_workspace.workspace_id', $workspaceId)
    //             ->where('share_links.url', $url)
    //             ->whereNull('share_links.deleted_at')
    //             ->when($shareLinkId, function ($query) use ($shareLinkId) {
    //                 $query->where('share_links.id', '!=', $shareLinkId);
    //             })
    //             ->exists();

    //         if ($exists) {
    //             $validator->errors()->add('url', 'The URL has already been taken in this workspace.');
    //         }
    //     });
    // }
}
