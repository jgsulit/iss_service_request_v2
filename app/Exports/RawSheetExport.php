<?php

namespace App\Exports;

use App\Model\Ticket;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Contracts\View\View;

class RawSheetExport implements FromView, WithTitle
{
    public function view(): View
    {
        $tickets = Ticket::where('logdel', 0)
						->with([
							'requestor_info',
                            'assignee_info',
                            'second_assignee_info',
                            'service_type_info',
                            'department_info'
                        ])
                ->where('logdel', 0)
                ->get();

        return view('exports.raw', compact('tickets'));
    }

    /**
     * Return the title for the sheet
     */
    public function title(): string
    {
        return 'RAW'; // Name of the first sheet
    }
}
