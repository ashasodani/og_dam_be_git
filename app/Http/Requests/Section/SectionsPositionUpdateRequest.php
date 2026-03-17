<?php
namespace App\Http\Requests\Section;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class SectionsPositionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'position' => 'required|integer',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $position    = $this->input('position');
            $workspaceId = $this->input('workspace_id');
            $section     = $this->route('section'); // can be model or ID
            $sectionId   = is_object($section) ? $section->id : $section;

            if (! $position || ! $workspaceId || ! $sectionId) {
                return;
            }

            $exists = DB::table('sections')
                ->join('workspace_sections', 'sections.id', '=', 'workspace_sections.section_id')
                ->where('workspace_sections.workspace_id', $workspaceId)
                ->where('sections.position', $position)
                ->where('sections.id', '!=', $sectionId)
                ->whereNull('sections.deleted_at')
                ->exists();

            if ($exists) {
                $validator->errors()->add('position', 'The section position already exists in this workspace.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'position.required'     => 'The position field is required.',
            'position.integer'      => 'The position must be a valid integer.',
            'workspace_id.required' => 'The workspace ID is required.',
            'workspace_id.exists'   => 'The selected workspace does not exist.',
        ];
    }
}
