<?php

namespace App\Imports;


use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Models\Person;
use App\Models\Company;
use App\Models\Country;
use App\Models\Port;
use Maatwebsite\Excel\Concerns\WithSheetName;

class SpecificSheetImport implements WithSheetName, ToModel, WithHeadingRow
{
    private $sheetName;

    public function __construct($sheetName)
    {
        $this->sheetName = $sheetName;
    }
    public function sheetName(): string
    {
        return $this->sheetName;
    }
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
   

    public function model(array $row)
    {
       dd($row,$this->sheetName);
  //  dd($this->sheetName);
                $country = Country::select('id',)
                 ->where('sheet_name', '=', $this->sheetName)
                 ->value('id');
                 $countryId = $country;
                if($row['ports']){
        // $port = Port::create([
        //     'port_name'     => $row['ports'],
        //             'sheet_id'=>$country->id
        // ]);

        $port = Port::create(
        [
            'port_name'     => $row['ports'],
            'country_id'=>$countryId
        ]);


    } else {
       $port=Port::orderByDesc('id')->first();
    }
    // dd([
    //     'port_name'     => $row['ports'],
    //     'sheet_id'=>$countryId
    // ]);
    //dd($port->port_name);
    // Level 3 - Company
    if($row['company_name']){
    // $company = Company::create([
    //     'company_name'    => $row['company_name'],
    //                 'company_email_id'    => $row['company_email_id'],
    //                 'company_contact_no'    => $row['contact_no'],
    //                 'company_website'    => $row['company_website'],
    //                 'port_id'    => $port->id
    // ]);
        
    $company = Company::create([
                    'company_name'    => $row['company_name'],
                    'port_name'    => $port->port_name,
                    'company_website'    => $row['company_website'],
                    'port_id'    => $port->id,
                    'country_id'    => $countryId
    ]);
    }else{
        $company=Company::orderByDesc('id')->first();
    }

    // Level 4 - Person
    if($row['contact_person_name']){
        return Person::create(
            [
            'contact_person_name'    => $row['contact_person_name'],
            'designation'    => $row['position_designation'],
            'contact_person_email_id'    => $row['contact_person_email_id'],
            'contact_person_no'    => $row['contact_person_phone_no'],
            'company_id'    => $company->id
        ]);
    }
   
    }
}
