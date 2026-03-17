<?php
namespace App\Http\Requests\Collection;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class CollectionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'    => 'required|string|min:1|max:255',
            'slug'    => 'required|string|min:1|max:255',
            'privacy' => 'sometimes|in:public,private,stealth',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $slug         = $this->input('slug');
            $workspaceId  = $this->input('workspace_id');
            $collection   = $this->route('collection'); // can be ID or model
            $collectionId = is_object($collection) ? $collection->id : $collection;

            if (! $slug || ! $workspaceId || ! $collectionId) {
                return;
            }

            $exists = DB::table('collections')
                ->join('workspace_collections', 'collections.id', '=', 'workspace_collections.collection_id')
                ->where('workspace_collections.workspace_id', $workspaceId)
                ->where('collections.slug', $slug)
                ->where('collections.id', '!=', $collectionId)
                ->whereNull('collections.deleted_at')
                ->exists();

            if ($exists) {
                $validator->errors()->add('slug', 'The collection slug already exists in this workspace.');
            }
        });
    }
}
