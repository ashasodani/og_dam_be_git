<?php
namespace App\Imports;

use App\Models\Persons;
use App\Models\Companies;
use App\Models\Countries;
use App\Models\Ports;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;

class FirstSheetImport implements ToModel, WithHeadingRow
{
    private $sheetName;
    public function __construct($sheetName)
    {
        $this->sheetName = $sheetName;
    }
        /**
         * @param array $row
         *
         * @return \Illuminate\Database\Eloquent\Model|null
         */
        /* 
        Process a single row of the Excel file.
        Country -> Port -> Company -> Person
        If the data is blank, the previous record is used.
        */
   public function model(array $row)
    {
        // Level 1 - Country
        $country = Countries::select('id',)
            ->where('sheet_name', '=', $this->sheetName)
            ->value('id');
        $countryId = $country;

        // Level 2 - Port
        if($row['ports']){
            // $port = Port::firstOrCreate(
            //     ['port_name' => $row['ports']], // Condition to check if the record exists
            //     [
            //         'port_name'     => $row['ports'],
            //         'country_id'=>$countryId
            //     ]
            // );
            $port = Ports::create(
            [
                'port_name'     => $row['ports'],
                'country_id'=>$countryId
            ]);


        } else {
            $port=Ports::orderByDesc('id')->first();
        }
  
        // Level 3 - Company
        if($row['company_name']){
        
            $company = Companies::create([
                            'company_name'    => $row['company_name']??'ash',
                            'port_name'    => $port->port_name,
                            'company_email_id'    => $row['company_email_id'],
                            'company_contact_no'    => $row['company_phone_no'],
                            'company_website'    => $row['company_website'],
                            'port_id'    => $port->id,
                            'country_id'    => $countryId
            ]);
        }else{
            $company=Companies::orderByDesc('id')->first();
        }

        // Level 4 - Person
        if($row['contact_person_name']){
            return Persons::create(
                [
                'contact_person_name'    => $row['contact_person_name'],
                'designation'    => $row['position_designation'],
                'contact_person_email_id'    => $row['contact_person_email_id'],
                'contact_person_no'    => $row['contact_no'],
                'company_id'    => $company->id
            ]);
        }
    }
    
}
