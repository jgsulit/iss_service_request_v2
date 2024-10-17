<!DOCTYPE html>
<html>
<head>
    <title>KPI_TRT</title>
    <style>
        table, th, td {
            border: 1px solid black;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px;
            text-align: center;
        }
        thead th {
            background-color: green;
            color: white;
        }
    </style>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th>FY {{ \Carbon\Carbon::now()->year }}</th>
                @foreach ($months as $monthName)
                    <th colspan="2">{{ $monthName }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr>
                <th>Monthly Ticket Volume</th>
                @foreach ($months as $monthName)
                    <th>Hit</th>
                    <th>Miss</th>
                @endforeach
            </tr>
            @foreach ($arr_trt as $trtKey => $trtValue)
                <tr>
                    <td>{{ $trtValue }}</td>
                    @foreach ($months as $monthIndex => $monthName)
                        <td>{{ $monthlyData[$trtKey][$monthIndex]['hit'] ?? 0 }}</td>
                        <td>{{ $monthlyData[$trtKey][$monthIndex]['miss'] ?? 0 }}</td>
                    @endforeach
                </tr>
            @endforeach
            <tr>
                <td>Total</td>
                @foreach ($months as $monthIndex => $monthName)
                    @php
                        $totalHit = 0;
                        $totalMiss = 0;
                        foreach ($arr_trt as $trtKey => $trtValue) {
                            $totalHit += $monthlyData[$trtKey][$monthIndex]['hit'] ?? 0;
                            $totalMiss += $monthlyData[$trtKey][$monthIndex]['miss'] ?? 0;
                        }
                    @endphp
                    <td>{{ $totalHit }}</td>
                    <td>{{ $totalMiss }}</td>
                @endforeach
            </tr>
            <tr>
                <td>Total Ticket Volume</td>
                @foreach ($months as $monthIndex => $monthName)
                    @php
                        $totalHit = 0;
                        $totalMiss = 0;
                        foreach ($arr_trt as $trtKey => $trtValue) {
                            $totalHit += $monthlyData[$trtKey][$monthIndex]['hit'] ?? 0;
                            $totalMiss += $monthlyData[$trtKey][$monthIndex]['miss'] ?? 0;
                        }
                    @endphp
                    <td colspan="2">{{ $totalHit + $totalMiss }}</td>
                @endforeach
            </tr>
            <tr>
                <td>Overall Missed %</td>
                @foreach ($months as $monthIndex => $monthName)
                    @php
                        $totalHit = 0;
                        $totalMiss = 0;
                        foreach ($arr_trt as $trtKey => $trtValue) {
                            $totalHit += $monthlyData[$trtKey][$monthIndex]['hit'] ?? 0;
                            $totalMiss += $monthlyData[$trtKey][$monthIndex]['miss'] ?? 0;
                        }

                        $total = $totalHit + $totalMiss;
                        $result = 0;
                        if($total > 0) {
                            $result = round(($totalMiss / ($totalHit + $totalMiss)) * 100, 2);
                        }
                    @endphp
                    <td colspan="2">{{ $result }}%</td>
                @endforeach
            </tr>
            <tr>
                <td>Overall Hit %</td>
                @foreach ($months as $monthIndex => $monthName)
                    @php
                        $totalHit = 0;
                        $totalMiss = 0;
                        foreach ($arr_trt as $trtKey => $trtValue) {
                            $totalHit += $monthlyData[$trtKey][$monthIndex]['hit'] ?? 0;
                            $totalMiss += $monthlyData[$trtKey][$monthIndex]['miss'] ?? 0;
                        }

                        $total = $totalHit + $totalMiss;
                        $result = 0;
                        if($total > 0) {
                            $result = round(($totalHit / ($totalHit + $totalMiss)) * 100, 2);
                        }
                    @endphp
                    <td colspan="2">{{ $result }}%</td>
                @endforeach
            </tr>
            <tr>
                <td><br></td>
            </tr>
        </tbody>
    </table>

    <table>
        <tr>
            <th>Legend</th>
        </tr>
        <tr>
            <td>E4 - 4 Hours</td>
        </tr>
        <tr>
            <td>R1 - 1 Day</td>
        </tr>
        <tr>
            <td>R2 - 2 Days</td>
        </tr>
        <tr>
            <td>R3 - 3 Days</td>
        </tr>
        <tr>
            <td>R4 - 4 Days</td>
        </tr>
        <tr>
            <td>R5 - 5 Days</td>
        </tr>
        <tr>
            <td>Rx - Indefinite (Has Dependencies)</td>
        </tr>
        <tr>
            <td><br></td>
        </tr>
        <tr>
            <td>- 100.00 Hit Rate</td>
        </tr>
    </table>
</body>
</html>
