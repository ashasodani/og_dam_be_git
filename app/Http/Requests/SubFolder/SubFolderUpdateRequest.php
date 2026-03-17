<?php
namespace App\Http\Requests\SubFolder;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class SubFolderUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "name"       => "required|min:1|string|max:255",
            "slug"       => "required|string|min:1|max:255",
            "section_id" => "required|int|exists:sections,id",
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $slug        = $this->input('slug');
            $workspaceId = $this->input('workspace_id');
            $subfolder   = $this->route('subfolder'); // model or ID
            $subfolderId = is_object($subfolder) ? $subfolder->id : $subfolder;

            if (! $slug || ! $workspaceId || ! $subfolderId) {
                return;
            }

            $exists = DB::table('sub_folders')
                ->join('workspace_sub_folder', 'sub_folders.id', '=', 'workspace_sub_folder.subfolder_id')
                ->where('workspace_sub_folder.workspace_id', $workspaceId)
                ->where('sub_folders.slug', $slug)
                ->where('sub_folders.id', '!=', $subfolderId)
                ->whereNull('sub_folders.deleted_at')
                ->exists();

            if ($exists) {
                $validator->errors()->add('slug', 'The name already exists in this workspace.');
            }
        });
    }

    public function messages(): array
    {
        return [
            // name
            "name.required"       => "The name field is required.",
            "name.min"            => "The name must be at least :min character.",
            "name.max"            => "The name may not be greater than :max characters.",
            "name.string"         => "The name must be a valid string.",

            // slug
            "slug.required"       => "The slug field is required.",
            "slug.min"            => "The slug must be at least :min character.",
            "slug.max"            => "The slug may not be greater than :max characters.",
            "slug.string"         => "The slug must be a valid string.",

            // section
            "section_id.required" => "The section field is required.",
            "section_id.int"      => "The section must be a valid integer.",
            "section_id.exists"   => "The selected section does not exist.",
        ];
    }
}
