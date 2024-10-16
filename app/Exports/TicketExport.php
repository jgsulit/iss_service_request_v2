<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TicketExport implements WithMultipleSheets
{
    use Exportable;

    public function sheets(): array
    {
        return [
            new RawSheetExport(),
            new KpiTrtSheetExport(),
        ];
    }
}
