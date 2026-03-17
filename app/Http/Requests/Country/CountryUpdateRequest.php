<?php
namespace App\Http\Requests\Country;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class CountryUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'country_name' => 'required|string|min:1|max:255',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $country     = $this->route('country');
            $countryId   = is_object($country) ? $country->id : $country;
            $countryName = $this->input('country_name');
            $countryName = strtolower($countryName);

            if (! $countryId || ! $countryName) {
                return;
            }

            $exists = DB::table('countries')
                ->whereRaw('LOWER(country_name) = ?', [$countryName])
                ->where('id', '!=', $countryId)
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                $validator->errors()->add('country_name', 'The country name already exists.');
            }
        });
    }

    public function messages()
    {
        return [
            'country_name.required' => 'The country name field is required.',
            'country_name.string'   => 'The country name must be a valid string.',
            'country_name.min'      => 'The country name must be at least 1 character long.',
            'country_name.max'      => 'The country name must not exceed 255 characters.',
        ];
    }
}

