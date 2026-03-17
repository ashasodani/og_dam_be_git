<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DirectAlphaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $result = [];
        
        foreach ($this->resource as $letter => $companies) {
            $result[$letter] = [
                'companies' => $companies->map(function ($company) {
                    return [
                        'id' => $company->id,
                        'company_name' => $company->company_name,
                        'port_name' => $company->port->port_name ?? null,
                        'country_name' => $company->country->country_name ?? null,
                    ];
                })->values()
            ];
        }
        
        return $result;
    }
}