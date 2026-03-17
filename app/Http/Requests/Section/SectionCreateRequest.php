<?php
namespace App\Http\Requests\Section;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SectionCreateRequest extends FormRequest
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
    // public function rules(): array
    // {
    //     return [
    //         "workspace_id"       => 'required|int|' . Rule::exists(Workspaces::class, 'id')->whereNull('deleted_at'),
    //         "name"               => "required|string|min:1|max:255|" . Rule::unique(Sections::class, "name")->whereNull("deleted_at"),
    //         "name"               => ['required', 'string', 'min:1', 'max:255', Rule::unique(Sections::class, "name")->whereNull("deleted_at")
    //                 ->where(function ($query) {
    //                     $query->where('workspace_id', $this->workspace_id);
    //                 }),
    //         ],
    //         "position"           => "required|integer",
    //         "default_asset_type" => "required|in:files,colors,press/links",
    //         'collection_id'      => 'nullable|integer|exists:collections,id',
    //     ];
    // }

    public function rules(): array
    {
        return [
            'workspace_id'       => 'required|int|exists:workspaces,id,deleted_at,NULL',
            'name'               => 'required|string|min:1|max:255',
            'position'           => 'required|integer',
            'default_asset_type' => 'required|in:files,colors,press/links',
            'collection_id'      => 'nullable|integer|exists:collections,id',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $workspaceId = $this->input('workspace_id');
            $name        = $this->input('name');

            $exists = DB::table('sections')
                ->join('workspace_sections', 'sections.id', '=', 'workspace_sections.section_id')
                ->where('workspace_sections.workspace_id', $workspaceId)
                ->whereNull('sections.deleted_at')
                ->where('sections.name', $name)
                ->exists();

            if ($exists) {
                $validator->errors()->add('name', 'The section name must be unique within the selected workspace.');
            }
        });
    }

}
