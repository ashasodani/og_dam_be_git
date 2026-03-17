<?php

namespace App\Http\Requests\Workspace;

use App\Models\Workspaces;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class WorkspaceCreateRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            "name" => "required|min:1|string|max:255",
            "description" => "nullable|max:255",
            "slug" => "required|string|min:1|max:255|".Rule::unique(Workspaces::class, "slug")->whereNull("deleted_at"),
            "privacy" => "required|in:public,private,stealth",
            'thumbnail' => 'nullable|file|mimes:jpg,jpeg,png,webp,svg|max:5048',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'thumbnail.required' => 'Please upload an image.',
            'thumbnail.mimes' => 'Only JPEG, PNG, JPG, and SVG, WEBP formats are allowed.',
            'thumbnail.max' => 'The image size must not exceed 2MB.',
            'thumbnail.uploaded'    => 'Ensure thumbnail image is below 2MB and of a valid format.',
        ];
    }
}
