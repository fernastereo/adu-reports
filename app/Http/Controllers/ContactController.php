<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Contact;
use App\Models\Appointment;
use App\Models\Opportunity;
use App\Models\SalesPerson;
use Illuminate\Http\Request;
use App\Services\GoogleSheet;
use App\Models\ContactCustomField;


class ContactController extends Controller
{
    function contactreport($startDate)
    {
        try {
            ini_set('max_execution_time', 360);
            $result = [];

            $from = date($startDate);
            $contactData = Contact::where('dateAdded', '>=', $from)->get()->toArray();

            $salesPerson = SalesPerson::all()->toArray();
            $opportunities = Opportunity::all()->toArray();


            foreach ($contactData as $elem) {
                $contactId = $elem['contactid'];

                $callMeetingCalendar = Appointment::where('contactId', $contactId)
                    ->where('calendarId', config('constants.callMeetingCalendarId'))
                    ->first();

                if (isset($callMeetingCalendar)) {
                    $callMeetingAppointmentStatus = $callMeetingCalendar['appoinmentStatus'];

                    $onSiteCalendar = Appointment::where('contactId', $contactId)
                        ->where('calendarId', config('constants.onSiteEvaluationCalendarId'))
                        ->first();

                    $onSiteEvaluationAppointmentStatus = '';
                    if (isset($onSiteCalendar)) {
                        $onSiteEvaluationAppointmentStatus = $onSiteCalendar['appoinmentStatus'];
                    }

                    $assignedUserId = $callMeetingCalendar["assignedUserId"];
                    if (isset($assignedUserId)) {
                        $salesPersonName = $this->findInArray($salesPerson, 'assignedUserId',  $assignedUserId);
                    }

                    $contractorNotes = "";
                    $customFields = [];
                    $customFields = ContactCustomField::where('contact_id', $elem['id'])->where('customFieldId', config('constants.contractorNotesId'))->get()->toArray();
                    if (count($customFields) > 0) {
                        $filtered_arr = array_filter(
                            $customFields,
                            function ($obj) {
                                return $obj['customFieldId'] === config('constants.contractorNotesId');
                            }
                        );

                        if (count($filtered_arr) > 0) {
                            $key = array_keys($filtered_arr)[0];
                            $contractorNotes = $filtered_arr[$key]["value"];
                        }
                    }

                    $meetingFeedback = "";
                    $customFields = [];
                    $customFields = ContactCustomField::where('contact_id', $elem['id'])->where('customFieldId', config('constants.meetingFeedbackId'))->get()->toArray();
                    if (count($customFields) > 0) {
                        $filtered_arr = array_filter(
                            $customFields,
                            function ($obj) {
                                return $obj['customFieldId'] === config('constants.meetingFeedbackId');
                            }
                        );

                        if (count($filtered_arr) > 0) {
                            $key = array_keys($filtered_arr)[0];
                            $meetingFeedback = $filtered_arr[$key]["value"];
                        }
                    }

                    $disposition = "";
                    $customFields = [];
                    $customFields = ContactCustomField::where('contact_id', $elem['id'])->where('customFieldId', config('constants.dispositionId'))->get()->toArray();
                    if (count($customFields) > 0) {
                        $filtered_arr = array_filter(
                            $customFields,
                            function ($obj) {
                                return $obj['customFieldId'] === config('constants.dispositionId');
                            }
                        );

                        if (count($filtered_arr) > 0) {
                            $key = array_keys($filtered_arr)[0];
                            $disposition = $filtered_arr[$key]["value"];
                        }
                    }

                    $opportunityWon = "";
                    $contractSent = "";
                    if (isset($contactId)) {
                        $opportunity = $this->findInArray($opportunities, 'contactid',  $contactId);
                        if (count($opportunity) > 0) {
                            if ($opportunity['status'] === "won") {
                                $opportunityWon = "X";
                                $contractSent = "";
                            } else {
                                $opportunityWon = "";
                                $contractSent = "X";
                            }
                        }
                    }

                    $item = [
                        'appointmentid' => $callMeetingCalendar['appointmentid'],
                        'date' => $callMeetingCalendar['appointmentstartTime'],
                        'contactId' => $callMeetingCalendar['contactId'],
                        'customerName' => $callMeetingCalendar['contactfirstName'] . ' ' . $callMeetingCalendar['contactlastName'],
                        'assignedUserId' => $callMeetingCalendar['assignedUserId'],
                        'salesPerson' => count($salesPersonName) > 0 ? $salesPersonName['name'] : '',
                        'callMeeting' => $callMeetingAppointmentStatus,
                        'onSite' => $onSiteEvaluationAppointmentStatus,
                        'contractSent' => $contractSent,
                        'opportunityWon' => $opportunityWon,
                        'appointmentSetterNotes' => $contractorNotes,
                        'disposition' => $disposition,
                        'salesPersonFeedback' => $meetingFeedback,
                    ];
                    array_push($result, $item);
                }
            }
            return response()->json(
                [
                    'success' => true,
                    'params' => [
                        'startDate' => $startDate,
                        'endDate' => '',
                    ],
                    'data' => $result,
                    'error' => [
                        'message' => '',
                    ]
                ],
                200
            );
        } catch (Exception $e) {
            $errorCode = $e->getCode();
            $error = "";
            switch ($errorCode) {
                case '42S22':
                    $error = "Cannot query to database ($errorCode)";
                    break;

                default:
                    # code...
                    break;
            }
            return response()->json(
                [
                    'success' => false,
                    'params' => [
                        'startDate' => $startDate,
                        'endDate' => '',
                    ],
                    'data' => [],
                    'error' => [
                        'message' => $e->getMessage(),
                    ]
                ],
                200
            );
        }
    }

    function exportData(Request $request, GoogleSheet $googleSheet)
    {
        try {
            $input = $request->all();
            $prevData = $input["data"];
            $data = [];
            foreach ($prevData as $value) {
                $elemData = [];
                $date = date("d-m-Y", strtotime($value["date"]));
                $customerName = $value["customerName"] === null ? '' : $value["customerName"];
                $salesPerson = $value["salesPerson"] === null ? '' : $value["salesPerson"];
                $callMeeting = $value["callMeeting"] === null ? '' : $value["callMeeting"];
                $onSite = $value["onSite"] === null ? '' : $value["onSite"];
                $contractSent = $value["contractSent"] === null ? '' : $value["contractSent"];
                $opportunityWon = $value["opportunityWon"] === null ? '' : $value["opportunityWon"];
                $appointmentSetterNotes = $value["appointmentSetterNotes"] === null ? '' : $value["appointmentSetterNotes"];
                $disposition = $value["disposition"] === null ? '' : $value["disposition"];
                $salesPersonFeedback = $value["salesPersonFeedback"] === null ? '' : $value["salesPersonFeedback"];
                array_push($elemData, $date, $customerName, $salesPerson, $callMeeting, $onSite, $contractSent, $opportunityWon, $appointmentSetterNotes, $disposition, $salesPersonFeedback);
                array_push($data, $elemData);
            }

            $sheets = $googleSheet->saveRawDataToSheet($data, 'RawDataContacts');

            return response()->json(
                [
                    'success' => true,
                    'result' => 'exported',
                    'data' => $data,
                    'sheets' => $sheets,
                    'error' => [
                        'message' => '',
                    ]
                ],
                200
            );
        } catch (Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'result' => 'not exported',
                    'data' => $data,
                    'sheets' => '',
                    'error' => [
                        'message' => $e->getMessage(),
                    ]
                ],
                200
            );
        }
    }

    function findInArray($array, $field, $searchedValue)
    {
        $filtered_arr = array_filter(
            $array,
            function ($obj) use ($field, $searchedValue) {
                return $obj[$field] === $searchedValue;
            }
        );

        if (count($filtered_arr) > 0) {
            $key = array_keys($filtered_arr)[0];

            return $filtered_arr[$key];
        }
        return [];
    }
}
