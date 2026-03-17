<?php
namespace App\Http\Requests\Tag;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class TagUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => 'required|string|min:1|max:255',
            'asset_id' => 'nullable|exists:assets,id',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $tag         = $this->route('tag'); // can be model or ID
            $tagId       = is_object($tag) ? $tag->id : $tag;
            $name        = $this->input('name');
            $name = strtolower($name);
            $workspaceId = $this->input('workspace_id');

            if (! $tagId || ! $name || ! $workspaceId) {
                return;
            }

            $exists = DB::table('tags')
                ->join('workspace_tags', 'tags.id', '=', 'workspace_tags.tag_id')
                ->where('workspace_tags.workspace_id', $workspaceId)
                ->whereRaw('LOWER(tags.name) = ?', [$name])
               // ->where('tags.name', $name)
                ->where('tags.id', '!=', $tagId)
                ->whereNull('tags.deleted_at')
                ->exists();

            if ($exists) {
                $validator->errors()->add('name', 'The tag name already exists in this workspace.');
            }
        });
    }

    public function messages()
    {
        return [
            'name.required'         => 'The name field is required.',
            'name.string'           => 'The name must be a valid string.',
            'name.min'              => 'The name must be at least 1 character long.',
            'name.max'              => 'The name must not exceed 255 characters.',
            'asset_id.exists'       => 'The selected asset does not exist.',
            'workspace_id.required' => 'The workspace ID is required.',
            'workspace_id.exists'   => 'The selected workspace does not exist.',
        ];
    }
}
