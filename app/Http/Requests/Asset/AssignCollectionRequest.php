<?php
namespace App\Http\Requests\Asset;

use Illuminate\Foundation\Http\FormRequest;

class AssignCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'asset_id'        => 'array|required_without:subfolder_id',
            'asset_id.*'      => 'exists:assets,id',
            
            'subfolder_id'        => 'array|required_without:asset_id',
            'subfolder_id.*'      => 'exists:sub_folders,id',
            
            'collection_id'   => 'required|array',
            'collection_id.*' => 'exists:collections,id',
        ];
    }
}
