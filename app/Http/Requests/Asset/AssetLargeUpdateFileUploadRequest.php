<?php

namespace App\Http\Requests\Asset;

use App\Models\Assets;
use App\Models\Sections;
use App\Models\SubFolders;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class AssetLargeUpdateFileUploadRequest extends FormRequest
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
     * 
     */
    public function rules(): array
    {
        if ($this->isMethod('post')) {
            // Validation rules for store (POST)
            return [
                'attachments' => 'required|file|max:3072000',
            'chunk_index' => 'required|integer|min:0',
            'total_chunks' => 'required|integer|min:1',
            'filename' => 'required|string'
            ];
        } elseif ($this->isMethod('put') || $this->isMethod('patch')) {
            return [
                'attachments' => 'required|file|max:3072000',
                'chunk_index' => 'required|integer|min:0',
                'total_chunks' => 'required|integer|min:1',
                'filename' => 'required|string'
            ];
        }
    
        return [];

        
    }
}
