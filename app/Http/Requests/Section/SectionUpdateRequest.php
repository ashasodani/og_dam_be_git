<?php
namespace App\Http\Requests\Section;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class SectionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "workspace_id"       => 'required|int|exists:workspaces,id,deleted_at,NULL',
            "name"               => "required|max:255|string|min:1",
            "position"           => "required|integer",
            "default_asset_type" => "required|in:files,colors,press/links",
            'collection_id'      => 'nullable|integer|exists:collections,id',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $workspaceId = $this->input('workspace_id');
            $name        = $this->input('name');
            $section     = $this->route('section');
            $sectionId   = is_object($section) ? $section->id : $section;

            if (! $workspaceId || ! $name || ! $sectionId) {
                return;
            }

            $exists = DB::table('sections')
                ->join('workspace_sections', 'sections.id', '=', 'workspace_sections.section_id')
                ->where('workspace_sections.workspace_id', $workspaceId)
                ->where('sections.name', $name)
                ->where('sections.id', '!=', $sectionId)
                ->whereNull('sections.deleted_at')
                ->exists();

            if ($exists) {
                $validator->errors()->add('name', 'The section name already exists in this workspace.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'workspace_id.required'       => 'The workspace ID is required.',
            'workspace_id.int'            => 'The workspace ID must be an integer.',
            'workspace_id.exists'         => 'The selected workspace does not exist.',

            'name.required'               => 'The section name is required.',
            'name.string'                 => 'The section name must be a string.',
            'name.min'                    => 'The section name must be at least :min characters.',
            'name.max'                    => 'The section name must not exceed :max characters.',

            'position.required'           => 'The position field is required.',
            'position.integer'            => 'The position must be an integer.',

            'default_asset_type.required' => 'The asset type is required.',
            'default_asset_type.in'       => 'The asset type must be one of: files, colors, press/links.',

            'collection_id.integer'       => 'The collection ID must be an integer.',
            'collection_id.exists'        => 'The selected collection does not exist.',
        ];
    }
}
