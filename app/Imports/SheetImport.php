<?php
namespace App\Imports;

use App\Models\Person;
use App\Models\Company;
use App\Models\Country;
use App\Models\Port;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;

class SheetImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
     dd($row);
                $country = Country::select('id',)
                 ->where('sheet_name', '=', 'COLOMBIA-D')
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
                    'company_email_id'    => $row['company_email_id'],
                    'company_contact_no'    => $row['contact_no'],
                    'company_website'    => $row['company_website'],
                    'port_id'    => $port->id,
                    'country_id'    => $countryId
    ]);
    }else{
        $company=Company::orderByDesc('id')->first();
    }

    // Level 4 - Person
    if($row['contact_person_name']){
        // return Member::create([
        //     'contact_person_name'    => $row['contact_person_name'],
        //     'designation'    => $row['position_designation'],
        //     'contact_person_email_id'    => $row['contact_person_email_id'],
        //     'contact_person_no'    => $row['contact_person_phone_no'],
        //     'company_id'    => $company->id,
        //     'sheet_id'    => $country->id,
        // ]);
        // dd(['contact_person_name' => $row['contact_person_name'],'company_id'=>$company->id],[
        //     'contact_person_name'    => $row['contact_person_name'],
        //     'designation'    => $row['position_designation'],
        //     'contact_person_email_id'    => $row['contact_person_email_id'],
        //     'contact_person_no'    => $row['contact_person_phone_no'],
        //     'company_id'    => $company->id,
        //     'sheet_id'    => $countryId,
        // ]);
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
