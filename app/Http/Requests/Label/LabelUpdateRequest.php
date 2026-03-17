<?php
namespace App\Http\Requests\Label;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class LabelUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => 'required|string|min:1|max:255',
            'parent_key' => 'nullable|exists:labels,id',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $labelId     = $this->route('label'); // current label ID
            $workspaceId = $this->input('workspace_id');
            $name        = $this->input('name');
            $name = strtolower($name);

            if (! $workspaceId || ! $name || ! $labelId) {
                return;
            }

            $exists = DB::table('labels')
                ->join('workspace_labels', 'labels.id', '=', 'workspace_labels.label_id')
                ->where('workspace_labels.workspace_id', $workspaceId)
                //->where('labels.name', $name)
                ->whereRaw('LOWER(labels.name) = ?', [$name])
                ->whereNull('labels.deleted_at')
                ->where('labels.id', '!=', $labelId) // ignore current label
                ->exists();

            if ($exists) {
                $validator->errors()->add('name', 'The label name already exists in this workspace.');
            }
        });
    }
}
