<?php

namespace App\Http\Requests\Company;

use App\Models\Companies;
use App\Models\Countries;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class CompanyCreateRequest extends FormRequest
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
            'import_file' => 'required|mimes:xlsx,xls|max:2048',
            'country_id'        => ['required', 'array', 'min:1', 'distinct'],
            'country_id.*'      => ['required', 'int', 'distinct'],
        ];
    }
       public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $countryId = $this->input('country_id');

            if (! $countryId) {
                return;
            }

            // foreach ($names as $index => $name) {
            //     $name = strtolower($name);
            //     $exists = DB::table('tiles')
            //         ->where('portal_id', $portalId)
            //         ->whereRaw('LOWER(name) = ?', [$name])
            //         ->whereNull('deleted_at')
            //         ->exists();

            //     if ($exists) {
            //         $validator->errors()->add("names.$index", "The tile name \"$name\" already exists in this workspace.");
            //     }
            // }
        });
    }

      /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'import_file.required' => 'Please upload an Excel file.',
            'import_file.mimes' => 'Only xlsx and xls formats are allowed.',
            'import_file.max' => 'The file size must not exceed 2MB.',
            'import_file.uploaded'    => 'The file upload failed. Please check the file size and format.',
        ];
    }
}
