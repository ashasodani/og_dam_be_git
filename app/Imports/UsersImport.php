<?php

namespace App\Imports;

use App\Models\Member;
use App\Models\Country;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithConditionalSheets;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\ImportFailed;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Events\AfterImport;


class UsersImport implements WithHeadingRow,WithMultipleSheets,WithEvents
{
    private $sheetName;
    /**
     * Constructor.
     *
     * @param string $sheetName
     */
    public function __construct($sheetName)
    {
        $this->sheetName = $sheetName;
    }
    public function sheets(): array
    {
        return [
            $this->sheetName => new FirstSheetImport($this->sheetName),
        ];
    }
    public function registerEvents(): array
    {
        return [
            AfterImport::class => function (AfterImport $event) {
                // Your logic after import
            },
        ];
    }
}
