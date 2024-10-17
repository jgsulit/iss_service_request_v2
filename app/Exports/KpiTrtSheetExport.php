<?php

namespace App\Exports;

use App\Model\Ticket;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Contracts\View\View;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class KpiTrtSheetExport implements FromView, WithTitle
{
    public function view(): View
    {
        $currentYear = Carbon::now()->year;

        $tickets = Ticket::select(
            'trt',
            DB::raw('MONTH(created_at) as month'),
            DB::raw('COUNT(CASE WHEN confirmed_at IS NOT NULL AND confirmed_at <= due_date THEN 1 END) as hit_count'),
            DB::raw('COUNT(CASE WHEN confirmed_at IS NULL OR confirmed_at > due_date THEN 1 END) as missed_count')
        )
        ->whereYear('created_at', $currentYear) // Filter for the current year
        ->groupBy('trt', DB::raw('MONTH(created_at)')) // Group by 'trt' and month
        ->orderBy('month') // Sort by month
        ->get();

        // return response()->json([
        //     'tickets' => $tickets
        // ]);

        // Transform the data into a more usable format for the Blade view
        $monthlyData = [];
        foreach ($tickets as $ticket) {
            $trtIndex = $ticket->trt ?? ''; // Mapping null TRT to 'Rx'
            $month = $ticket->month;

            if (!isset($monthlyData[$trtIndex])) {
                $monthlyData[$trtIndex] = [];
            }

            $monthlyData[$trtIndex][$month] = [
                'hit' => $ticket->hit_count,
                'miss' => $ticket->missed_count
            ];
        }

        // TRT mapping
        $arr_trt = [
            '0.4' => 'E4',
            1 => 'R1',
            2 => 'R2',
            3 => 'R3',
            4 => 'R4',
            5 => 'R5',
            '-1' => 'RX',
        ];

        // Prepare months array
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        return view('exports.kpi_trt', compact('arr_trt', 'months', 'monthlyData'));
    }

    /**
     * Return the title for the sheet
     */
    public function title(): string
    {
        return 'KPI_TRT'; // Name of the first sheet
    }
}
