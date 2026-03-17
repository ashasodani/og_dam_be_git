<?php
namespace App\Http\Requests\Tag;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class TagCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'names'        => ['required', 'array', 'min:1', 'distinct'],
            'names.*'      => ['required', 'string', 'distinct'],
            'asset_id'     => ['nullable', 'int', 'exists:assets,id'],
            'workspace_id' => ['required', 'int', 'exists:workspaces,id'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $workspaceId = $this->input('workspace_id');
            $names       = $this->input('names');

            if (! $workspaceId || ! is_array($names)) {
                return;
            }

            foreach ($names as $index => $name) {
                $name = strtolower($name);
                $exists = DB::table('tags')
                    ->join('workspace_tags', 'tags.id', '=', 'workspace_tags.tag_id')
                    ->where('workspace_tags.workspace_id', $workspaceId)
                    //->where('tags.name', $name)
                    ->whereRaw('LOWER(tags.name) = ?', [$name])
                    ->whereNull('tags.deleted_at')
                    ->exists();

                if ($exists) {
                    $validator->errors()->add("names.$index", "The tag name \"$name\" already exists in this workspace.");
                }
            }
        });
    }

    public function messages()
    {
        return [
            'names.required'        => 'The names field is required.',
            'names.array'           => 'The names field must be an array.',
            'names.min'             => 'At least one tag name must be provided.',
            'names.distinct'        => 'The tag names must be distinct.',
            'names.*.required'      => 'Each tag name is required.',
            'names.*.string'        => 'Each tag name must be a string.',
            'names.*.distinct'      => 'Duplicate tag names are not allowed.',
            'asset_id.int'          => 'The asset ID must be an integer.',
            'asset_id.exists'       => 'The specified asset does not exist.',
            'workspace_id.required' => 'The workspace ID is required.',
            'workspace_id.int'      => 'The workspace ID must be an integer.',
            'workspace_id.exists'   => 'The specified workspace does not exist.',
        ];
    }
}
