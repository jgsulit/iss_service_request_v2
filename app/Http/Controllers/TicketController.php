<?php

namespace App\Http\Controllers;

// HELPERS
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Auth;

// MODEL
use App\Model\Ticket;
use App\Model\User;
use App\Model\TicketLog;
use App\Model\RapidXUser;
use App\Model\SystemOnePhoneDirectory;
// PACKAGE
use DataTables;
use Carbon\Carbon;
use Mail;

class TicketController extends Controller
{
	public function get_str_trt($trt) {
        if($trt != "") {

            // commented by Nessa 09102024
            // if($trt < 1) {
            //     return 'E4';
            // }
            // else {
                $arr_trt = [
                    "-1" => 'RX',
                    "0.4" => 'E4',
                    1 => 'R1',
                    2 => 'R2',
                    3 => 'R3',
                    4 => 'R4',
                    5 => 'R5',
                ];

                return $arr_trt[$trt];
            // }
        }
		else {
            return "N/A";
        }
	}

    public function view_open_tickets(Request $request){
        if($request->ajax()){
            $currentDateTime = Carbon::now()->toDateTimeString();
	        $data = Ticket::select('*', DB::raw("TIMESTAMPDIFF(SECOND, created_at, '{$currentDateTime}') as time_diff"))
                ->where('logdel', 0)
                ->where('status', 1)
                ->with([
                    'requestor_info',
                    'second_assignee_info',
                    'service_type_info',
                ])
                ->orderBy('id', 'desc')
                // ->orderBy('time_diff', 'desc')
                ->get();

            // Process each ticket to add a human-readable format
			$data->transform(function ($ticket) use ($currentDateTime) {
			    $createdAt = Carbon::parse($ticket->created_at);
			    $currentDate = Carbon::parse($currentDateTime);
			    $timeDiffInSeconds = $createdAt->diffInSeconds($currentDate);

			    // Convert the difference to days, hours, and minutes
			    $days = floor($timeDiffInSeconds / 86400); // 86400 seconds in a day
			    $hours = floor(($timeDiffInSeconds % 86400) / 3600); // 3600 seconds in an hour
			    $minutes = floor(($timeDiffInSeconds % 3600) / 60); // 60 seconds in a minute

			    // Format the result as "Xd Xh Xm"
			    $humanReadableDiff = '';
			    if ($days > 0) {
			        $humanReadableDiff .= "{$days}d ";
			    }
			    if ($hours > 0) {
			        $humanReadableDiff .= "{$hours}h ";
			    }
			    if ($minutes > 0 || empty($humanReadableDiff)) {
			        $humanReadableDiff .= "{$minutes}m";
			    }

			    $ticket->duration = $humanReadableDiff;

			    return $ticket;
			});

            return response()->json(['auth' => 1, 'result' => 1, 'data' => $data]);
        }
        else{
    		abort(403);
    	}
    }

    public function view_in_progress_tickets(Request $request){
        if($request->ajax()){
            $currentDateTime = Carbon::now()->toDateTimeString();

	        $data = Ticket::select('*',  DB::raw("TIMESTAMPDIFF(SECOND, '{$currentDateTime}', due_date) as time_diff"))
                // ->where('due_date', '>=', $currentDateTime)
                ->where('logdel', 0)
                ->whereIn('status', [2, 3])
                ->with([
                    'requestor_info',
                    'second_assignee_info',
                    'service_type_info',
                ])
                ->orderBy('time_diff', 'asc')
                ->get();

            // Process each ticket to add a human-readable format
			$data->transform(function ($ticket) use ($currentDateTime) {
			    $dueDate = Carbon::parse($ticket->due_date);
			    $currentDate = Carbon::parse($currentDateTime);
			    $timeDiffInSeconds = $dueDate->diffInSeconds($currentDate);

			    // Convert the difference to days, hours, and minutes
			    $days = floor($timeDiffInSeconds / 86400); // 86400 seconds in a day
			    $hours = floor(($timeDiffInSeconds % 86400) / 3600); // 3600 seconds in an hour
			    $minutes = floor(($timeDiffInSeconds % 3600) / 60); // 60 seconds in a minute

			    // Format the result as "Xd Xh Xm"
			    $humanReadableDiff = '';
			    if ($days > 0) {
			        $humanReadableDiff .= "{$days}d ";
			    }
			    if ($hours > 0) {
			        $humanReadableDiff .= "{$hours}h ";
			    }
			    if ($minutes > 0 || empty($humanReadableDiff)) {
			        $humanReadableDiff .= "{$minutes}m";
			    }

			    $ticket->duration = $humanReadableDiff;

			    return $ticket;
			});

            return response()->json(['auth' => 1, 'result' => 1, 'data' => $data]);
        }
        else{
    		abort(403);
    	}
    }

    //Tickets For ISS
    public function view_tickets(Request $request){
		session_start();
		$user = User::where('user_id', $_SESSION["rapidx_user_id"])->where('logdel', 0)->first();
		$admin = 0;
		$iss_staff = 0;
		if($user != null) {
            $admin = $user->admin;
            $iss_staff = $user->iss_staff;
		}

        if($request->ajax()){
	        $data = Ticket::where('logdel', 0)
						->with([
							'requestor_info',
                            'second_assignee_info',
                            'service_type_info'
                        ]);

            // eto ung sa diplay if iaallow sa lahat
            if($admin == 0 && $iss_staff == 1){
                $data = $data->where('second_assignee', $_SESSION["rapidx_user_id"]);
            }
            else {
            	if(isset($request->second_assignee) && $request->second_assignee != null) {
            		$data = $data->where('second_assignee', $request->second_assignee);
            	}
            }

            if($request->status == 0){
                // return'aa';
                $data = $data->where('logdel', 0)->get();
            }else{
                $data = $data->where('status', $request->status)->get();
            }
            // $data = $data->where('logdel', 0) // Added by Nessa 09102024


	        return DataTables::of($data)
	            ->addColumn('raw_status', function($row){
	                $result = "";

					$trt = "";
					if($row->status != 1){
						$trt = $this->get_str_trt($row->trt);
					}

	                if($row->status == 1){
	                    $result .= '<span class="badge badge-pill bg-secondary">Open</span>';
	                }
	                else if($row->status == 2){
	                    $result .= '<span class="badge badge-pill bg-warning">In Progress - ' . $trt . '</span>';
	                }
	                else if($row->status == 3){
	                    $result .= '<span class="badge badge-pill bg-purple">For Verification - ' . $trt . '</span>';
	                }
	                else if($row->status == 4){
	                    $result .= '<span class="badge badge-pill bg-success">Confirmed - ' . $trt . '</span>';
	                }
	                else if($row->status == 5){
	                    $result .= '<span class="badge badge-pill bg-danger">Cancelled - ' . $trt . '</span>';
	                }
					else if($row->status == 6){
	                    $result .= '<span class="badge badge-pill bg-danger">Closed - ' . $trt . '</span>';
	                }

	                return $result;
	            })
	            ->addColumn('raw_action', function($row) use ($admin, $iss_staff){
	                $result = '';
	                if($row->status == 1){
	                    // $result .= '<button type="button" class="btn btn-xs btn-primary table-btns btnEditTicket" ticket-id="' . $row->id . '"><i class="fa fa-edit" title="Edit"></i></button>';

	                    if($admin == 1) {
							$result .= '<button type="button" class="btn btn-xs btn-primary table-btns btnAssignTicket" ticket-id="' . $row->id . '" title="Assign"><i class="fa fa-user"></i></button>';
							$result .= ' <button type="button" class="btn btn-xs btn-danger table-btns btnCancelTicket" action="1" status="5" ticket-id="' . $row->id . '" title="Cancel"><i class="fa fa-lock"></i></button>';
						}

	                }
                    else if($row->status == 2){

	                    if($admin == 1) {
                            $assignee = "";
                            $assignee_name = "";

                            if($row->second_assignee != null) {
                                $assignee = $row->second_assignee;
                                $assignee_name = $row->second_assignee_info->name;
                            }

                            // if(Carbon::now()->format('Y-m-d H:i:s') <= $row->due_date) {
                            	$result .= ' <button type="button" class="btn btn-xs btn-warning table-btns btnReassignTicket"
                            ticket-id="' . $row->id . '"
                            trt="' . $row->trt . '"
                            service-type-id="' . $row->service_type_id . '"
                            service-type-description="' . $row->service_type_info->description . '"
                            assignee="' . $assignee . '"
                            asignee_name="' . $assignee_name . '"
                            title="Submit"><i class="fa fa-users"></i></button>';
                            // }
						}

                        // $result .= "<h1>" . $iss_staff . "</h1>";

                        if($admin == 1 || $iss_staff == 1) {
                            $result .= ' <button type="button" class="btn btn-xs btn-success table-btns btnVerifyTicket" ticket-id="' . $row->id . '" title="For verification"><i class="fa fa-check"></i></button>';
                        }
	                }
                    else if($row->status == 3){

                        if($admin == 1) {
                            $result .= ' <button type="button" class="btn btn-xs btn-success table-btns btnActions" action="1" status="4" ticket-id="' . $row->id . '" title="Confirm"><i class="fa fa-check-circle"></i></button>';
                        }

                        // comment muna 09142024 by Nessa
                        // else {
                        //     if($iss_staff == 1) {
                        //         $diff_in_mins = Carbon::now()->diffInMinutes(Carbon::parse($row->for_verification_at));

                        //         if($diff_in_mins >= 30) {
                        //             $result .= ' <button type="button" class="btn btn-xs btn-success table-btns btnActions" action="1" status="4" ticket-id="' . $row->id . '" title="Confirm"><i class="fa fa-check-circle"></i></button>';
                        //         }
                        //     }
                        // }


	                    // if($admin == 1) {
                        //     $assignee = $row->assignee;
                        //     $assignee_name = $row->assignee_info->name;

                        //     if($row->second_assignee != null) {
                        //         $assignee = $row->second_assignee;
                        //         $assignee_name = $row->second_assignee_info->name;
                        //     }
						// 	$result .= ' <button type="button" class="btn btn-xs btn-warning table-btns btnReassignTicket"
                        //     ticket-id="' . $row->id . '"
                        //     trt="' . $row->trt . '"
                        //     service-type-id="' . $row->service_type_id . '"
                        //     service-type-description="' . $row->service_type_info->description . '"
                        //     assignee="' . $assignee . '"
                        //     asignee_name="' . $assignee_name . '"
                        //     title="Reassign"><i class="fa fa-users"></i></button>';
						// }

                        // if($admin == 1 || $iss_staff == 1) {
                        //     $result .= ' <button type="button" class="btn btn-xs btn-success table-btns btnVerifyTicket" ticket-id="' . $row->id . '" title="For verification"><i class="fa fa-check"></i></button>';
                        // }
	                }
	                else{
	                    // $result .= ' <button type="button" class="btn btn-xs btn-success table-btns btnActions" action="1" status="1" ticket-id="' . $row->id . '" title="Restore"><i class="fa fa-unlock"></i></button>';
	                }

                    $result .= ' <button type="button" class="btn btn-xs btn-primary table-btns btnViewTicketLogs" ticket-id="' . $row->id . '" title="View Logs"><i class="fa fa-file"></i></button>';

	                return $result;
	            })
	            ->addColumn('raw_cc', function($row){
	                return str_replace(",", "<br>", $row->cc);
	            })
	            ->addColumn('raw_cc', function($row){
	                return str_replace(",", "<br>", $row->cc);
	            })
	            ->addColumn('raw_subject', function($row){
	            	$subject = $row->subject;
	            	if($row->attachments != null) {
	            		$path = url("storage/app/public/attachments/" . $row->attachments);
	                	$subject .= '<br><span style="font-size: 12px; color: blue;"><i class="fa fa-paperclip"></i> <a style="color: blue;" href="' . $path . '" target="_blank">' . Str::substr($row->attachments, 14) . '<a/></span>';
	                }
	                return $subject;
	            })
	            ->addColumn('raw_created_at', function($row){
	                $result = Carbon::parse($row->created_at)->toFormattedDateString() . ' ' . Carbon::parse($row->created_at)->format('h:i A') . '<br>';

	                if($row->assigned_at != null) {
	                	$result .= Carbon::parse($row->assigned_at)->toFormattedDateString() . ' ' . Carbon::parse($row->assigned_at)->format('h:i A') . '<br>';
	                }
	                else{
	                	$result .= '--<br>';
	                }

	                if($row->due_date != null) {
	                	$result .= Carbon::parse($row->due_date)->toFormattedDateString() . ' ' . Carbon::parse($row->due_date)->format('h:i A') . '<br>';
	                }
	                else{
	                	$result .= '--<br>';
	                }

	                if($row->confirmed_at != null) {
	                	$result .= '<span style="color:green;">' . Carbon::parse($row->confirmed_at)->toFormattedDateString() . ' ' . Carbon::parse($row->confirmed_at)->format('h:i A') . '</span><br>';
	                }
	                else{
	                	$result .= '--<br>';
	                }


	               	return $result;
	            })
                ->addColumn('raw_requestor', function($row){
	                $result = $row->requestor_info->name . '<br>';

                    if($row->second_assignee_info != null) {
	                	// $result .= $row->second_assignee_info->name . '<br>';
	                	$result .= '<span style="color:blue;">' . $row->second_assignee_info->name . '</span><br>';
	                }
	                else{
                        if($row->assignee_info != null) {
                            // $result .= $row->assignee_info->name . '<br>';
                            $result .= '<span style="color:blue;font-weight:bold">' . $row->assignee_info->name . '</span><br>';
                        }
                        else{
                            $result .= '--<br>';
                        }
	                }

	               	return $result;
	            })
                ->addColumn('raw_description', function($row){
	                $result = '';

                    if($row->service_type_info != null) {
                        $result .= $row->service_type_info->description;
                    }
                    else{
                        $result .= '--<br>';
                    }

	               	return $result;
	            })
	            ->rawColumns(['raw_status', 'raw_action', 'raw_cc', 'raw_subject', 'raw_created_at', 'raw_requestor', 'raw_description'])
	            ->make(true);
        }
    	else{
    		abort(403);
    	}
    }

    public function view_my_tickets(Request $request){
    	session_start();
        if($request->ajax()){
	        $data = Ticket::with([
                            'assignee_info',
                            'second_assignee_info',
                            'service_type_info',
                        ])
                        ->where('logdel', 0)
	        			->where('created_by', $_SESSION["rapidx_user_id"])
	        			->where('status', $request->status)
        				->get();

	        return DataTables::of($data)
	            ->addColumn('raw_status', function($row){
	                $result = "";

					$trt = "";
					if($row->status != 1){
						$trt = $this->get_str_trt($row->trt);
					}

	                if($row->status == 1){
	                    $result .= '<span class="badge badge-pill bg-secondary">Open</span>';
	                }
	                else if($row->status == 2){
	                    $result .= '<span class="badge badge-pill bg-warning">In Progress - ' . $trt . '</span>';
	                }
	                else if($row->status == 3){
	                    $result .= '<span class="badge badge-pill bg-purple">For Verification - ' . $trt . '</span>';
	                }
	                else if($row->status == 4){
	                    $result .= '<span class="badge badge-pill bg-success">Confirmed - ' . $trt . '</span>';
	                }
	                else if($row->status == 5){
	                    $result .= '<span class="badge badge-pill bg-danger">Cancelled - ' . $trt . '</span>';
	                }
					else if($row->status == 6){
	                    $result .= '<span class="badge badge-pill bg-danger">Closed - ' . $trt . '</span>';
	                }

	                return $result;
	            })
	            ->addColumn('raw_action', function($row){
	                $result = '';

	                // $result .= '<button type="button" class="btn btn-xs btn-primary table-btns btnViewTicket" ticket-id="' . $row->id . '"><i class="fa fa-file" title="View"></i></button>';

	                if($row->status == 1){
	                    // $result .= '<button type="button" class="btn btn-xs btn-primary table-btns btnEditTicket" ticket-id="' . $row->id . '"><i class="fa fa-edit" title="Edit"></i></button>';

	                    $result .= ' <button type="button" class="btn btn-xs btn-danger table-btns btnCancelTicket" action="1" status="5" ticket-id="' . $row->id . '" title="Cancel"><i class="fa fa-times"></i></button>';
	                }
                    else if($row->status == 3){
                        $result .= ' <button type="button" class="btn btn-xs btn-success table-btns btnActions" action="1" status="4" ticket-id="' . $row->id . '" title="Confirm"><i class="fa fa-check-circle"></i></button>';
                    }

                    $result .= ' <button type="button" class="btn btn-xs btn-primary table-btns btnViewTicketLogs" ticket-id="' . $row->id . '" title="View Logs"><i class="fa fa-file"></i></button>';

	                return $result;
	            })
	            ->addColumn('raw_cc', function($row){
	                return str_replace(",", "<br>", $row->cc);
	            })
	            ->addColumn('raw_cc', function($row){
	                return str_replace(",", "<br>", $row->cc);
	            })
	            ->addColumn('raw_subject', function($row){
	            	$subject = $row->subject;
	            	if($row->attachments != null) {
	            		$path = url("storage/app/public/attachments/" . $row->attachments);
	                	$subject .= '<br><br><span style="font-size: 12px; color: blue;"><i class="fa fa-paperclip"></i> <a style="color: blue;" href="' . $path . '" target="_blank">' . Str::substr($row->attachments, 14) . '<a/></span>';
	                }
	                return $subject;
	            })
	            ->addColumn('raw_created_at', function($row){
	                $result = Carbon::parse($row->created_at)->toFormattedDateString() . ' ' . Carbon::parse($row->created_at)->format('h:i A') . '<br>';

	                if($row->assigned_at != null) {
	                	$result .= Carbon::parse($row->assigned_at)->toFormattedDateString() . ' ' . Carbon::parse($row->assigned_at)->format('h:i A') . '<br>';
	                }
	                else{
	                	$result .= '--<br>';
	                }

	                if($row->due_date != null) {
	                	$result .= Carbon::parse($row->due_date)->toFormattedDateString() . ' ' . Carbon::parse($row->due_date)->format('h:i A') . '<br>';
	                }
	                else{
	                	$result .= '--';
	                }

	                if($row->confirmed_at != null) {
	                	$result .= Carbon::parse($row->confirmed_at)->toFormattedDateString() . ' ' . Carbon::parse($row->confirmed_at)->format('h:i A') . '<br>';
	                }
	                else{
	                	$result .= '--<br>';
	                }

	               	return $result;
	            })
	            ->rawColumns(['raw_status', 'raw_action', 'raw_cc', 'raw_subject', 'raw_created_at'])
	            ->make(true);
        }
    	else{
    		abort(403);
    	}
    }

    // Tickets For Requestor
    public function save_ticket(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        if($request->ajax()){
	        if(isset($_SESSION["rapidx_user_id"])){
		        // Add Ticket
		        if(!isset($request->ticket_id)){
		            $data = $request->all();

		            $rules = [
		                'subject' => 'required',
		                'requests' => 'required',
		                'local_no' => 'required', // Added by Nessa 08312024
		            ];

		            $validator = Validator::make($data, $rules);
                    DB::beginTransaction();
		            try {
		                if($validator->passes()){
		                	$attachments = null;
		                	if ($request->hasFile('attachments')) {
					            if ($request->file('attachments')->isValid()) {
					                $attachments = date('YmdHis') . $request->attachments->getClientOriginalName();
					                $request->attachments->storeAs('/public/attachments/', $attachments);
					            }
					        }

					        $cc = null;
					        if(isset($request->cc)) {
					        	$cc = implode(",", $request->cc);
					        }

		                    $ticket_id = Ticket::insertGetId([
		                        'cc' => $cc,
		                        'subject' => $request->subject,
		                        'local_no' => $request->local_no, // Added by Nessa 08312024
		                        'request' => $request->requests,
		                        'status' => 1,
		                        'attachments' => $attachments,
		                        'requestor' => $_SESSION["rapidx_user_id"],
		                        'department_id' => $_SESSION["rapidx_department_id"],
		                        'created_by' => $_SESSION["rapidx_user_id"],
		                        'last_updated_by' => $_SESSION["rapidx_user_id"],
		                        'created_at' => date('Y-m-d H:i:s'),
		                        'updated_at' => date('Y-m-d H:i:s'),
		                    ]);

                            $ticket_data = Ticket::where('id', $ticket_id)->first();

                            TicketLog::insert([
		                        'ticket_id' => $ticket_id,
                                'action' => 1,
                                'data' => $ticket_data,
		                        'description' => 'The ticket #' . $ticket_id . ' has been created.',
		                        'created_by' => $_SESSION["rapidx_user_id"],
		                        'last_updated_by' => $_SESSION["rapidx_user_id"],
		                        'created_at' => date('Y-m-d H:i:s'),
		                        'updated_at' => date('Y-m-d H:i:s'),
		                    ]);

							// $email_content = 'The ticket #' . $ticket_id . ' has been created. Request: ' . $ticket_data->request;

                            // Added by Nessa 09052024
							$email_content1 = 'New ticket # has been added.';
							$email_content2 = 'Date Created: '.$ticket_data->created_at;
							$email_content3 = 'Subject: '.$ticket_data->subject;
							$email_content4 = 'Request: '.$ticket_data->request;
							$email_content5 = 'Local Number: '.$ticket_data->local_no;

							// $to = [RapidXUser::where('id', $ticket_data->requestor)->first()->email];

                            // Added by Nessa 09252024
							// $to = 'jgsulit@pricon.ph';
							$to = 'servicerequest@pricon.ph';
							$cc = [];

							if($ticket_data->cc != null) {
								$cc = explode(',', $ticket_data->cc);
							}

							// Mail::send('mail.ticket', ['email_content' => $email_content], function($message) use($to, $cc, $ticket_data){

                            // Updated by Nessa 09252024
                            Mail::send('mail.ticket', ['email_content1' => $email_content1,
                                                    'email_content2' => $email_content2,
                                                    'email_content3' => $email_content3,
                                                    'email_content4' => $email_content4,
                                                    'email_content5' => $email_content5
                            ], function($message) use($to, $cc, $ticket_data){
								$message->to($to)->cc($cc)->subject('ISS Service Request - Ticket #' . $ticket_data->id . ": " . $ticket_data->subject);
							});
                            DB::commit();
		                    return response()->json(['auth' => 1, 'result' => 1, 'error' => null]);
		                }
		                else{
		                    return response()->json(['auth' => 1, 'result' => 0, 'error' => $validator->messages()]);
		                }
		            }
		            catch(\Exception $e) {
                        DB::rollback();
		                return response()->json(['auth' => 1, 'result' => 0, 'error' => $e]);
		            }
		        }
		        // Edit Ticket
		        else{
		            $data = [
		                'ticket_id' => $request->ticket_id,
		                'description' => $request->description,
		            ];

		            $rules = [
		                'ticket_id' => 'required|numeric',
		                'description' => 'required|min:2|unique:tickets,description,' . $request->ticket_id,
		            ];

		            $validator = Validator::make($data, $rules);

		            try {
		                if($validator->passes()){
		                    Ticket::where('id', $request->ticket_id)
		                    	->where('logdel', 0)
		                    	->where('status', 1)
		                        ->update([
		                            'description' => $request->description,
		                            'last_updated_by' => $_SESSION["rapidx_user_id"],
		                            'updated_at' => date('Y-m-d H:i:s'),
		                        ]);
		                    return response()->json(['auth' => 1, 'result' => 1, 'error' => null]);
		                }
		                else{
		                    return response()->json(['auth' => 1, 'result' => 0, 'error' => $validator->messages()]);
		                }
		            }
		            catch(\Exception $e) {
		                return response()->json(['auth' => 1, 'result' => 0, 'error' => $e]);
		            }
		        }
	        }
	        else{
	        	return response()->json(['auth' => 0, 'result' => 0, 'error' => null]);
	        }
	    }
    	else{
    		abort(403);
    	}
    }

    public function get_ticket_by_id(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();
        if($request->ajax()){
	        if(isset($_SESSION["rapidx_user_id"])){
		        $data = [
		            'ticket_id' => $request->ticket_id,
		        ];

		        $rules = [
		            'ticket_id' => 'required',
		        ];

		        $validator = Validator::make($data, $rules);

		        if($validator->passes()){
		            $ticket_info = Ticket::where('id', $request->ticket_id)->where('logdel', 0)->first();

		            return response()->json(['auth' => 1, 'ticket_info' => $ticket_info, 'result' => 1]);
		        }
		        else{
		            return response()->json(['auth' => 1, 'ticket_info' => null, 'result' => 0]);
		        }
		    }
		    else{
	        	return response()->json(['auth' => 0, 'result' => 0, 'error' => null]);
		    }
		}
    	else{
    		abort(403);
    	}
    }

    public function get_ticket_logs_by_id(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();
        if($request->ajax()){
	        if(isset($_SESSION["rapidx_user_id"])){
		        $data = [
		            'ticket_id' => $request->ticket_id,
		        ];

		        $rules = [
		            'ticket_id' => 'required',
		        ];

		        $validator = Validator::make($data, $rules);

		        if($validator->passes()){
		            $ticket_logs = TicketLog::where('ticket_id', $request->ticket_id)->where('logdel', 0)->orderBy('id', 'asc')->get();

		            return response()->json(['auth' => 1, 'ticket_logs' => $ticket_logs, 'result' => 1]);
		        }
		        else{
		            return response()->json(['auth' => 1, 'ticket_logs' => [], 'result' => 0]);
		        }
		    }
		    else{
	        	return response()->json(['auth' => 0, 'result' => 0, 'error' => null]);
		    }
		}
    	else{
    		abort(403);
    	}
    }

    public function ticket_action(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        if($request->ajax()){
	        // Change Ticket Status
	        if(isset($_SESSION["rapidx_user_id"])){
		        if($request->action == 1){
		            $data = [
		                'ticket_id' => $request->ticket_id,
		                'status' => $request->status,
		            ];

		            $rules = [
		                'ticket_id' => 'required',
		                'status' => 'required|numeric',
		            ];

		            $validator = Validator::make($data, $rules);

		            if($validator->passes()){
                        DB::beginTransaction();
		                try {

                            $update_data = [
                                'status' => $request->status,
                                'last_updated_by' => $_SESSION["rapidx_user_id"],
                                'updated_at' => date('Y-m-d H:i:s'),
                            ];

                            if($request->status == 4) {
                                $update_data["confirmed_at"] = date('Y-m-d H:i:s');
                                $update_data["confirmed_by"] = $_SESSION["rapidx_user_id"];
                            }

                            if($request->status == 5) {
                                $update_data["cancel_remarks"] = $request->cancel_remarks;
                            }

		                    Ticket::where('id', $request->ticket_id)
		                    	->where('logdel', 0)
		                        ->update($update_data);

							$ticket_data = Ticket::where('id', $request->ticket_id)->first();

                            $log_description = "";
							$subject = "";

                            if($request->status == 5) {
                                $log_description = 'The ticket has been cancelled by the requestor. With a remarks of: "' . $request->cancel_remarks . '".';
								$subject = 'ISS Service Request - Ticket #' . $ticket_data->id . ": CANCELLED : " . $ticket_data->subject;
                            }
                            else if($request->status == 4) {
                                $confirmed_by = RapidXUser::where('id', $_SESSION["rapidx_user_id"])->first()->name;
                                $log_description = "The ticket has been confirmed by " . $confirmed_by  . " at " . Carbon::now()->toFormattedDateString() . ' ' . Carbon::now()->format('h:i A') . ".";
								$subject = 'ISS Service Request - Ticket #' . $ticket_data->id . ": CONFIRMED : " . $ticket_data->subject;
                            }

							$email_content1 = $log_description;

							$email_content2 = 'Date Created: '.$ticket_data->created_at;
		                    $email_content3 = 'Subject: '.$ticket_data->subject;
		                    $email_content4 = 'Request: '.$ticket_data->request;
		                    $email_content5 = 'Local Number '.$ticket_data->local_no;

							$to = [RapidXUser::where('id', $ticket_data->requestor)->first()->email];
							$cc = [];

							if($ticket_data->cc != null) {
								$cc = explode(',', $ticket_data->cc);
							}

							Mail::send('mail.ticket', [
									'email_content1' => $email_content1,
									'email_content2' => $email_content2,
									'email_content3' => $email_content3,
									'email_content4' => $email_content4,
									'email_content5' => $email_content5,
								],
								function($message) use($to, $cc, $ticket_data, $subject){
									$message->to($to)->cc($cc)->subject($subject);
								}
							);

                            TicketLog::insert([
                                'ticket_id' => $request->ticket_id,
                                'action' => 4,
                                'data' => $ticket_data,
                                'description' => $log_description,
                                'created_by' => $_SESSION["rapidx_user_id"],
                                'last_updated_by' => $_SESSION["rapidx_user_id"],
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);

                            DB::commit();
		                    return response()->json(['auth' => 1, 'result' => 1, 'error']);
		                }
		                catch (Exception $e) {
                            DB::rollback();
		                    return response()->json(['auth' => 1, 'ticket_info' => null]);
		                }
		            }
		            else{
		                return response()->json(['auth' => 1, 'result' => 0, 'error' => $validator->messages()]);
		            }
		        }
	        } // Session Expired
		    else{
	        	return response()->json(['auth' => 0, 'result' => 0, 'error' => null]);
		    }
		}
    	else{
    		abort(403);
    	}
    }

	public function assign_ticket(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        if($request->ajax()){
			$data = $request->all();

			$rules = [
				'ticket_id' => 'required',
				'assignee' => 'required|numeric',
				'service_type_id' => 'required',
				'trt' => 'required',
			];

			$validator = Validator::make($data, $rules);

			if($validator->passes()){
                DB::beginTransaction();
				try {
					$service_type_id = explode(" - ", $request->service_type_id)[0];
					$today = Carbon::now();
					if($request->trt < 0){
						$due_date = Carbon::parse($today)->addMonths(1)->format('Y-m-d H:i:s');
					}
					else if($request->trt >= 1) {
						$arr_dates = [];
						$now = Carbon::now()->timezone('Asia/Manila');
						$now_curr_day = Carbon::now()->dayOfWeek;

						$date_skipped_ctr = 0;

						$arr_dates[] = [
							'comp_date' => $now,
							'current_day' => $now_curr_day,
						];

						if($now_curr_day == 0) {
							$date_skipped_ctr++;
						}

						for($index = 1; $index <= $request->trt; $index++) {
							$comp_date = Carbon::now()->addDays($index)->format('Y-m-d H:i:s');
							$curr_day = Carbon::now()->addDays($index)->dayOfWeek;
							if($curr_day == 0) {
								$date_skipped_ctr++;
							}
							$arr_dates[] = [
								'comp_date' => $comp_date,
								'current_day' => $curr_day,
							];
						}

                        // Put the logic here the holiday comparison

						$due_date = Carbon::parse($today)->addDays(($request->trt + $date_skipped_ctr))->format('Y-m-d H:i:s');

						// return response()->json([
						// 	'now' => $now,
						// 	'due_date' => $due_date,
						// 	'dates' => $arr_dates,
						// 	'date_skipped_ctr' => $date_skipped_ctr,
						// 	'auth' => 1,
						// 	'result' => 0,
						// 	'error' => null
						// ]);

					}
					else {
						$due_date = Carbon::parse($today)->addHours(($request->trt * 10))->format('Y-m-d H:i:s');
					}

					Ticket::where('id', $request->ticket_id)
						->where('logdel', 0)
						->update([
							'service_type_id' => $service_type_id,
							'trt' => $request->trt,
							'assignee' => $request->assignee,
							'second_assignee' => $request->assignee,
							'due_date' => $due_date,
							'status' => 2,
							'last_updated_by' => $_SESSION["rapidx_user_id"],
							'assigned_by' => $_SESSION["rapidx_user_id"],
							'assigned_at' => date('Y-m-d H:i:s'),
							'updated_at' => date('Y-m-d H:i:s'),
						]);

                    $assignee_name = RapidXUser::where('id', $request->assignee)->first()->name;
                    $ticket_data = Ticket::where('id', $request->ticket_id)->first();

					$description = 'The ticket has been assigned to ' .
                        $assignee_name . ' as ' . $this->get_str_trt($request->trt) . ' that will due on ' . Carbon::parse($due_date)->toFormattedDateString() . ' ' . Carbon::parse($due_date)->format('h:i A') . '.';

                    TicketLog::insert([
                        'ticket_id' => $request->ticket_id,
                        'action' => 2,
                        'data' => $ticket_data,
                        'description' => $description,
                        'created_by' => $_SESSION["rapidx_user_id"],
                        'last_updated_by' => $_SESSION["rapidx_user_id"],
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

					// $email_content = $description;

                    // Added by Nessa 09052024
                    $email_content1 = $description;
                    $email_content2 = 'Date Created: '.$ticket_data->created_at;
                    $email_content3 = 'Subject: '.$ticket_data->subject;
                    $email_content4 = 'Request: '.$ticket_data->request;
                    $email_content5 = 'Local Number '.$ticket_data->local_no;


					$to = [RapidXUser::where('id', $ticket_data->requestor)->first()->email];
					$cc = [];

					if($ticket_data->cc != null) {
						$cc = explode(',', $ticket_data->cc);
					}else{
                        $cc = [];
                    }

					$cc[] = RapidXUser::where('id', $ticket_data->assignee)->first()->email;

					// Mail::send('mail.ticket', ['email_content' => $email_content], function($message) use($to, $cc, $ticket_data){

                    // Updated by Nessa 09052024
                    Mail::send('mail.ticket', ['email_content1' => $email_content1,
                                                'email_content2' => $email_content2,
                                                'email_content3' => $email_content3,
                                                'email_content4' => $email_content4,
                                                'email_content5' => $email_content5
                    ], function($message) use($to, $cc, $ticket_data){
						$message->to($to)->cc($cc)->subject('ISS Service Request - Ticket #' . $ticket_data->id . ": ASSIGNED : " . $ticket_data->subject);
					});

                    DB::commit();
					return response()->json(['auth' => 1, 'result' => 1, 'error' => null]);
				}
				catch (Exception $e) {
                    DB::rollback();
					return response()->json(['auth' => 1, 'ticket_info' => null]);
				}
			}
			else{
				return response()->json(['auth' => 1, 'result' => 0, 'error' => $validator->messages()]);
			}
		}
    	else{
    		abort(403);
    	}
    }

    public function reassign_ticket(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        if($request->ajax()){
			$data = $request->all();

			$rules = [
				'ticket_id' => 'required',
				'second_assignee' => 'required|numeric',
				'service_type_id' => 'required',
				'trt' => 'required',
				'reason' => 'required',
			];

			$validator = Validator::make($data, $rules);

			if($validator->passes()){
                DB::beginTransaction();
				try {
                    $ticket_info = Ticket::where('id', $request->ticket_id)->first();

                    if($ticket_info!= null) {
                    	if(Carbon::now()->format('Y-m-d H:i:s') > $ticket_info->due_date) {
	                    		return response()->json([
									'auth' => 1,
									'result' => 2, // Already over due
								]);
                    	}
                    }

					$service_type_id = explode(" - ", $request->service_type_id)[0];
					$assigned_at = Carbon::parse($ticket_info->assigned_at);
					if($request->trt < 0){
						$due_date = Carbon::parse($ticket_info->assigned_at)->addMonths(1)->format('Y-m-d H:i:s');
					}
					else if($request->trt >= 1) {
						$arr_dates = [];
						$now = Carbon::parse($ticket_info->assigned_at);
						$now_curr_day = Carbon::parse($ticket_info->assigned_at)->dayOfWeek;

						$date_skipped_ctr = 0;

						$arr_dates[] = [
							'comp_date' => $now,
							'current_day' => $now_curr_day,
						];

						if($now_curr_day == 0) {
							$date_skipped_ctr++;
						}

						for($index = 1; $index <= $request->trt; $index++) {
							$comp_date = Carbon::parse($ticket_info->assigned_at)->addDays($index)->format('Y-m-d H:i:s');
							$curr_day = Carbon::parse($ticket_info->assigned_at)->addDays($index)->dayOfWeek;
							if($curr_day == 0) {
								$date_skipped_ctr++;
							}
							$arr_dates[] = [
								'comp_date' => $comp_date,
								'current_day' => $curr_day,
							];
						}

                        // Put the logic here the holiday comparison

						$due_date = Carbon::parse($assigned_at)->addDays(($request->trt + $date_skipped_ctr))->format('Y-m-d H:i:s');

						// return response()->json([
						// 	'now' => $now,
						// 	'due_date' => $due_date,
						// 	'dates' => $arr_dates,
						// 	'date_skipped_ctr' => $date_skipped_ctr,
						// 	'auth' => 1,
						// 	'result' => 0,
						// 	'error' => null
						// ]);

					}
					else {
						$due_date = Carbon::parse($assigned_at)->addHours(($request->trt * 10))->format('Y-m-d H:i:s');
					}

					Ticket::where('id', $request->ticket_id)
						->where('logdel', 0)
						->update([
							'service_type_id' => $service_type_id,
							'trt' => $request->trt,
							'second_assignee' => $request->second_assignee,
                            'reassigned_at' => date('Y-m-d H:i:s'),
                            'reassigned_by' => $_SESSION["rapidx_user_id"],
                            'reassign_reason' => $request->reason,
							'due_date' => $due_date,
							'status' => 2,
							'last_updated_by' => $_SESSION["rapidx_user_id"],
							'updated_at' => date('Y-m-d H:i:s'),
						]);

                    $assignee_name = RapidXUser::where('id', $request->second_assignee)->first()->name;
                    $ticket_data = Ticket::where('id', $request->ticket_id)->first();

					$description = 'The ticket has been reassigned to ' .
                        $assignee_name . ' as ' . $this->get_str_trt($request->trt) . ' that will due on ' . Carbon::parse($due_date)->toFormattedDateString() . ' ' . Carbon::parse($due_date)->format('h:i A')  . '. With a reason of: "' . $request->reason . '".';

                    TicketLog::insert([
                        'ticket_id' => $request->ticket_id,
                        'action' => 7, // reassigned
                        'data' => $ticket_data,
                        'description' => $description,
                        'created_by' => $_SESSION["rapidx_user_id"],
                        'last_updated_by' => $_SESSION["rapidx_user_id"],
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                    // $email_content = $description;

					// Added by Nessa 09052024
                    $email_content1 = $description;
                    $email_content2 = 'Date Created: '.$ticket_data->created_at;
                    $email_content3 = 'Subject: '.$ticket_data->subject;
                    $email_content4 = 'Request: '.$ticket_data->request;
                    $email_content5 = 'Local Number '.$ticket_data->local_no;

					$to = [RapidXUser::where('id', $ticket_data->requestor)->first()->email];
					$cc = [];

					if($ticket_data->cc != null) {
						$cc = explode(',', $ticket_data->cc);
					}

					$cc[] = RapidXUser::where('id', $ticket_data->second_assignee)->first()->email;

					// Mail::send('mail.ticket', ['email_content' => $email_content], function($message) use($to, $cc, $ticket_data){

                    //Updated by Nessa 09052024
                    Mail::send('mail.ticket', ['email_content1' => $email_content1,
                                            'email_content2' => $email_content2,
                                            'email_content3' => $email_content3,
                                            'email_content4' => $email_content4,
                                            'email_content5' => $email_content5
                    ], function($message) use($to, $cc, $ticket_data){
						$message->to($to)->cc($cc)->subject('ISS Service Request - Ticket #' . $ticket_data->id . ": REASSIGNED : " . $ticket_data->subject);
					});

                    DB::commit();
					return response()->json(['auth' => 1, 'result' => 1, 'error' => null]);
				}
				catch (Exception $e) {
                    DB::rollback();
					return response()->json(['auth' => 1, 'ticket_info' => null]);
				}
			}
			else{
				return response()->json(['auth' => 1, 'result' => 0, 'error' => $validator->messages()]);
			}
		}
    	else{
    		abort(403);
    	}
    }

    public function for_verification_ticket(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        if($request->ajax()){
			$data = $request->all();

			$rules = [
				'ticket_id' => 'required',
				'remarks' => 'required',
				'initial_assessment_summary' => 'required',
				'root_cause' => 'required',
				'procedure_title' => 'required',
				'resolution_procedure' => 'required',
				'material_used' => 'required',
			];

			$validator = Validator::make($data, $rules);

			if($validator->passes()){
                DB::beginTransaction();
				try {
					Ticket::where('id', $request->ticket_id)
						->where('logdel', 0)
						->update([
                            'remarks' => $request->remarks,
							'for_verification_at' => date('Y-m-d H:i:s'),
                            'for_verification_by' => $_SESSION["rapidx_user_id"],
							'status' => 3,
							'date_time_resolved' => date('Y-m-d H:i:s'),
							'initial_assessment_summary' => $request->initial_assessment_summary,
							'root_cause' => $request->root_cause,
							'procedure_title' => $request->procedure_title,
							'resolution_procedure' => $request->resolution_procedure,
							'material_used' => $request->material_used,
							'last_updated_by' => $_SESSION["rapidx_user_id"],
							'updated_at' => date('Y-m-d H:i:s'),
						]);

                    $for_verification_by_name = RapidXUser::where('id', $_SESSION["rapidx_user_id"])->first()->name;
                    $ticket_data = Ticket::where('id', $request->ticket_id)->first();

					$description = 'The ticket has been moved to "for verification" by ' .
                        $for_verification_by_name . '.<br><br>';

                    $description .= 'Remarks:<br>' . $request->remarks . '<br><br>';
                    $description .= 'Initial Assessment Summary:<br>' . $request->initial_assessment_summary . '<br><br>';
                    $description .= 'Root Cause:<br>' . $request->root_cause . '<br><br>';
                    $description .= 'Procedure Title/MOP:<br>' . $request->procedure_title . '<br><br>';
                    $description .= 'Resolution Procedure:<br>' . $request->resolution_procedure . '<br><br>';
                    $description .= 'Material /Tools and Equipment used:<br>' . $request->material_used . '<br><br>';

                    TicketLog::insert([
                        'ticket_id' => $request->ticket_id,
                        'action' => 3,
                        'data' => $ticket_data,
                        'description' => $description,
                        'created_by' => $_SESSION["rapidx_user_id"],
                        'last_updated_by' => $_SESSION["rapidx_user_id"],
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

					// $email_content = $description;

                    // Added by Nessa 09052024
                    $email_content1 = $description;
                    $email_content2 = 'Date Created: '.$ticket_data->created_at;
                    $email_content3 = 'Subject: '.$ticket_data->subject;
                    $email_content4 = 'Request: '.$ticket_data->request;
                    $email_content5 = 'Local Number '.$ticket_data->local_no;

					$to = [RapidXUser::where('id', $ticket_data->requestor)->first()->email];
					$cc = [];

					if($ticket_data->cc != null) {
						$cc = explode(',', $ticket_data->cc);
					}

                    // return $cc;

					// Mail::send('mail.ticket', ['email_content' => $email_content], function($message) use($to, $cc, $ticket_data){

                    // Updated by Nessa 09052024
                    Mail::send('mail.ticket', ['email_content1' => $email_content1,
                                            'email_content2' => $email_content2,
                                            'email_content3' => $email_content3,
                                            'email_content4' => $email_content4,
                                            'email_content5' => $email_content5
                    ], function($message) use($to, $cc, $ticket_data){
						$message->to($to)->cc($cc)->subject('ISS Service Request - Ticket #' . $ticket_data->id . ": FOR VERIFICATION : " . $ticket_data->subject);
					});

                    DB::commit();
					return response()->json(['auth' => 1, 'result' => 1, 'error' => null]);
				}
				catch (Exception $e) {
                    DB::rollback();
					return response()->json(['auth' => 1, 'ticket_info' => null]);
				}
			}
			else{
				return response()->json(['auth' => 1, 'result' => 0, 'error' => $validator->messages()]);
			}
		}
    	else{
    		abort(403);
    	}
    }

    public function comment_ticket(Request $request){
        date_default_timezone_set('Asia/Manila');
        session_start();

        if($request->ajax()){
			$data = $request->all();

			$rules = [
				'ticket_id' => 'required',
				'comment' => 'required',
			];

			$validator = Validator::make($data, $rules);

			if($validator->passes()){
                DB::beginTransaction();
				try {

                    $commentor_name = RapidXUser::where('id', $_SESSION["rapidx_user_id"])->first()->name;
                    $ticket_data = Ticket::where('id', $request->ticket_id)->first();

					$description = $commentor_name . ' commented with "' .
					$request->comment . '"';

                    TicketLog::insert([
                        'ticket_id' => $request->ticket_id,
                        'action' => 8, // comment
                        'data' => $ticket_data,
                        'description' => $description,
                        'created_by' => $_SESSION["rapidx_user_id"],
                        'last_updated_by' => $_SESSION["rapidx_user_id"],
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

					$email_content1 = $description;
					$email_content2 = 'Date Created: '.$ticket_data->created_at;
					$email_content3 = 'Subject: '.$ticket_data->subject;
					$email_content4 = 'Request: '.$ticket_data->request;
					$email_content5 = 'Local Number '.$ticket_data->local_no;

					$to = [RapidXUser::where('id', $ticket_data->requestor)->first()->email];
					$cc = [];

					if($ticket_data->cc != null) {
						$cc = explode(',', $ticket_data->cc);
					}

					if($ticket_data->second_assignee != null) {
						$cc[] = RapidXUser::where('id', $ticket_data->second_assignee)->first()->email;
					}

					Mail::send('mail.ticket', [
							'email_content1' => $email_content1,
							'email_content2' => $email_content2,
							'email_content3' => $email_content3,
							'email_content4' => $email_content4,
							'email_content5' => $email_content5,
						],
						function($message) use($to, $cc, $ticket_data){
							$message->to($to)->cc($cc)->subject('ISS Service Request - Ticket #' . $ticket_data->id . ": COMMENT : " . $ticket_data->subject);
						}
					);

                    DB::commit();
					return response()->json(['auth' => 1, 'result' => 1, 'error' => null]);
				}
				catch (Exception $e) {
                    DB::rollback();
					return response()->json(['auth' => 1, 'ticket_info' => null]);
				}
			}
			else{
				return response()->json(['auth' => 1, 'result' => 0, 'error' => $validator->messages()]);
			}
		}
    	else{
    		abort(403);
    	}
    }

    public function get_cbo_ticket_by_stat(Request $request){
        date_default_timezone_set('Asia/Manila');

        if($request->ajax()){
        	if(isset($_SESSION["rapidx_user_id"])){
		        $search = $request->search;

		        if($search == ''){
		            $tickets = [];
		        }
		        else{
		            $tickets = Ticket::orderby('description','asc')->select('id','description')
		                        ->where('description', 'like', '%' . $search . '%')
		                        ->where('status', 1)
		                        ->where('logdel', 0)
		                        ->get();
		        }

		        $response = array();
		        $response[] = array(
	                "id" => '',
	                "text" => '',
	            );

		        foreach($tickets as $ticket){
		            $response[] = array(
		                "id" => $ticket->id,
		                "text" => $ticket->description,
		            );
		        }

		        echo json_encode($response);
		        exit;
        	}
        	else{
        		$response = array();
		            $response[] = array(
		                "id" => '',
		                "text" => 'Please reload again.',
		            );

		        echo json_encode($response);
        	}
        }
    	else{
    		abort(403);
    	}
    }

    // 09122024 Added by Nessa
    public function get_local_no(){
        $phone_dir = SystemOnePhoneDirectory::where('category', 1)
        ->where('location', '!=', '')
        ->where('division_department', '!=', 'BOD')
        ->where('logdel',0)

        // ->where('logdel',0)
        ->get();
        // return $phone_dir;
        return response()->json(['phone_dir' => $phone_dir]);
    }
}

