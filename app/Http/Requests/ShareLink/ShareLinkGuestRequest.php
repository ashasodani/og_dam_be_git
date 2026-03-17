<?php
namespace App\Http\Requests\ShareLink;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShareLinkGuestRequest extends FormRequest
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
            'sharelink_url'             => 'required|string|min:1',  
        ];
    }
}
