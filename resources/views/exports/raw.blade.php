

<table border="1">
    <thead>
        <tr>
        <th>Ticket #</th>
        <th>Ticket Title</th>
        <th>Request	Create Date and Time</th>
        <th>FRT	Closure Date and Time</th>
        <th>Ticket Status</th>
        <th>Ticket Owner 1</th>
        <th>TRT</th>
        <th>Div / Dept / Sec</th>
        <th>Service Type</th>
        <th>FRT Delay</th>
        <th>Resolution Time</th>
        </tr>
    </thead>
    @php
        $arr_status = [
            1 => 'Open',
            2 => 'In Progress',
            3 => 'For Verification',
            4 => 'Confirmed',
            5 => 'Cancelled',
            6 => 'Closed',
        ];

        $arr_trt = [
            '' => '-',
            '-1' => 'RX',
            '0.4' => 'E4',
            1 => 'R1',
            2 => 'R2',
            3 => 'R3',
            4 => 'R4',
            5 => 'R5',
        ];
    @endphp
    <tbody>
        @foreach ($tickets as $ticket)

            @php

                // FRT Delay
                $startDate = \Carbon\Carbon::parse($ticket->created_at);
                $endDate = \Carbon\Carbon::parse($ticket->confirmed_at);
                // Get the difference as a CarbonInterval object
                $diff = $startDate->diffAsCarbonInterval($endDate);
                // Format the difference
                $days = $diff->d; // Number of days
                $hours = $diff->h; // Number of hours
                $minutes = $diff->i; // Number of minutes
                $frt_delay = "";
                if($days > 0) {
                    $frt_delay .= $days . 'd ';
                }
                if($hours > 0) {
                    $frt_delay .= $hours . 'h ';
                }
                if($minutes > 0) {
                    $frt_delay .= $minutes . 'm ';
                }

                $resolution_time = ' - ';
                if($ticket->confirmed_at != null) {
                    // Resolution Time
                    $startDate = \Carbon\Carbon::parse($ticket->confirmed_at);
                    $endDate = \Carbon\Carbon::parse($ticket->due_date);
                    // Get the difference as a CarbonInterval object
                    $diff = $startDate->diffAsCarbonInterval($endDate);
                    // Format the difference
                    $days = $diff->d; // Number of days
                    $hours = $diff->h; // Number of hours
                    $minutes = $diff->i; // Number of minutes
                    $resolution_time = "";
                    if($days > 0) {
                        $resolution_time .= $days . 'd ';
                    }
                    if($hours > 0) {
                        $resolution_time .= $hours . 'h ';
                    }
                    if($minutes > 0) {
                        $resolution_time .= $minutes . 'm ';
                    }
                }


                $department = "-";
                if($ticket->department_info != null) {
                    $department = $ticket->department_info->department_group . ' - ' . $ticket->department_info->department_name;
                }
            @endphp

            <tr>
                <td>{{ $ticket->id }}</td>
                <td>{{ $ticket->subject }}</td>
                <td>{{ $ticket->created_at }}</td>
                <td>{{ $ticket->confirmed_at }}</td>
                <td>{{ $arr_status[$ticket->status] }}</td>
                <td>{{ $ticket->assignee_info != null ? $ticket->assignee_info->name : '-'  }}</td>
                <td>{{ $arr_trt[(string)$ticket->trt] }}</td>
                <td>{{ $department }}</td>
                <td>{{ $ticket->service_type_info != null ? $ticket->service_type_info->description : '-'  }}</td>
                <td>{{ $frt_delay }}</td>
                <td>{{ $resolution_time }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
