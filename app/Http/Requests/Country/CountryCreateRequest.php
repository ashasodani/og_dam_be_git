<?php
namespace App\Http\Requests\Country;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class CountryCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'country_name'        => ['required', 'array', 'min:1', 'distinct'],
            'country_name.*'      => ['required', 'string', 'distinct'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $country_name       = $this->input('country_name');

            if (! is_array($country_name)) {
                return;
            }

            foreach ($country_name as $index => $country_name) {
                $country_name = strtolower($country_name);
                $exists = DB::table('countries')
                    ->whereRaw('LOWER(countries.country_name) = ?', [$country_name])
                    ->whereNull('countries.deleted_at')
                    ->exists();

                if ($exists) {
                    $validator->errors()->add("names.$index", "The Country name \"$country_name\" already exists.");
                }
            }
        });
    }

    public function messages()
    {
        return [
            'country_name.required'        => 'The names field is required.',
            'country_name.array'           => 'The names field must be an array.',
            'names.min'             => 'At least one tag name must be provided.',
            'country_name.distinct'        => 'The tag names must be distinct.',
            'country_name.*.required'      => 'Each tag name is required.',
            'country_name.*.string'        => 'Each tag name must be a string.',
            'country_name.*.distinct'      => 'Duplicate tag names are not allowed.',
        ];
    }
}
