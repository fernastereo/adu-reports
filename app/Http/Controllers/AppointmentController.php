<?php

namespace App\Http\Controllers;

use DateTime;
use Exception;
use App\Models\Contact;
use App\Models\ContactTag;
use App\Models\Appointment;
use App\Models\Opportunity;
use App\Models\SalesPerson;
use Illuminate\Http\Request;
use App\Services\GoogleSheet;
use App\Models\AppointmentTag;
use App\Models\ContactCustomField;
use Illuminate\Support\Facades\DB;
use App\Models\AppointmentAttachment;
use App\Models\AppointmentCustomField;
use App\Services\APIClient;

class AppointmentController extends Controller
{
    protected $client;

    public function __construct(APIClient $apiClient)
    {
        $this->client = $apiClient;
    }

    public function appointmentReport($startDate, $endDate)
    {
        try {
            ini_set('max_execution_time', 360);
            $result = [];

            $from = date($startDate);
            $to = date($endDate);
            $callMeetingCalendar = Appointment::where('calendarId', config('constants.callMeetingCalendarId'))
                ->whereBetween('appointmentstartTime', [$from, $to])
                ->get()
                ->toArray();

            $onSiteCalendar = Appointment::where('calendarId', config('constants.onSiteEvaluationCalendarId'))
                ->whereBetween('appointmentstartTime', [$from, $to])
                ->get()
                ->toArray();

            $salesPerson = SalesPerson::all()->toArray();
            $opportunities = Opportunity::all()->toArray();

            foreach ($callMeetingCalendar as $elem) {
                $contactId = $elem['contactId'];
                $onSiteEvaluationAppointmentStatus = $this->findInArray($onSiteCalendar, 'contactId', $contactId);

                $assignedUserId = $elem["assignedUserId"];
                if (isset($assignedUserId)) {
                    $salesPersonName = $this->findInArray($salesPerson, 'assignedUserId',  $assignedUserId);
                }

                //look for contact data
                $contactData = Contact::where('contactid', $contactId)->first();

                if ($contactData === null) {
                    //if the contact doesn't exists then look for it on the API and create it in local DB
                    $contactData = $this->syncContact($contactId);
                }

                $contractorNotes = "";
                $customFields = [];
                $customFields = ContactCustomField::where('contact_id', $contactData->id)->where('customFieldId', config('constants.contractorNotesId'))->get()->toArray();
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
                $customFields = ContactCustomField::where('contact_id', $contactData->id)->where('customFieldId', config('constants.meetingFeedbackId'))->get()->toArray();
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
                $customFields = ContactCustomField::where('contact_id', $contactData->id)->where('customFieldId', config('constants.dispositionId'))->get()->toArray();
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
                    'appointmentid' => $elem['appointmentid'],
                    'date' => $elem['appointmentstartTime'],
                    'contactId' => $elem['contactId'],
                    'customerName' => $elem['contactfirstName'] . ' ' . $elem['contactlastName'],
                    'assignedUserId' => $elem['assignedUserId'],
                    'salesPerson' => count($salesPersonName) > 0 ? $salesPersonName['name'] : '',
                    'callMeeting' => $elem['appointmentStatus'],
                    'onSite' => count($onSiteEvaluationAppointmentStatus) > 0 ? $onSiteEvaluationAppointmentStatus['appointmentStatus'] : '',
                    'contractSent' => $contractSent,
                    'opportunityWon' => $opportunityWon,
                    'appointmentSetterNotes' => $contractorNotes,
                    'disposition' => $disposition,
                    'salesPersonFeedback' => $meetingFeedback,
                ];
                array_push($result, $item);
            }
            return response()->json(
                [
                    'success' => true,
                    'params' => [
                        'startDate' => $startDate,
                        'endDate' => $endDate,
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
                        'endDate' => $endDate,
                    ],
                    'data' => [],
                    'error' => [
                        'message' => $error,
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

            $sheets = $googleSheet->saveRawDataToSheet($data, 'RawDataAppointments');

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

    function syncContact($contactId)
    {
        ini_set('max_execution_time', 360);
        $data = $this->client->get("https://rest.gohighlevel.com/v1/contacts/$contactId");

        if ($data["contact"]) {
            DB::beginTransaction();
            $newContact = Contact::create([
                'contactid' => isset($data["contact"]['id']) ? $data["contact"]['id'] : null,
                'locationId' => isset($data["contact"]['locationId']) ? $data["contact"]['locationId'] : null,
                'contactName' => isset($data["contact"]['contactName']) ? $data["contact"]['contactName'] : null,
                'firstName' => isset($data["contact"]['firstName']) ? $data["contact"]['firstName'] : null,
                'lastName' => isset($data["contact"]['lastName']) ? $data["contact"]['lastName'] : null,
                'companyName' => isset($data["contact"]['companyName']) ? $data["contact"]['companyName'] : null,
                'email' => isset($data["contact"]['email']) ? $data["contact"]['email'] : null,
                'phone' => isset($data["contact"]['phone']) ? $data["contact"]['phone'] : null,
                'dnd' => isset($data["contact"]['dnd']) ? $data["contact"]['dnd'] : null,
                'type' => isset($data["contact"]['type']) ? $data["contact"]['type'] : null,
                'source' => isset($data["contact"]['source']) ? $data["contact"]['source'] : null,
                'assignedTo' => isset($data["contact"]['assignedTo']) ? $data["contact"]['assignedTo'] : null,
                'city' => isset($data["contact"]['city']) ? $data["contact"]['city'] : null,
                'state' => isset($data["contact"]['state']) ? $data["contact"]['state'] : null,
                'postalCode' => isset($data["contact"]['postalCode']) ? $data["contact"]['postalCode'] : null,
                'address1' => isset($data["contact"]['address1']) ? $data["contact"]['address1'] : null,
                'dateAdded' => isset($data["contact"]['dateAdded']) ? new DateTime($data["contact"]['dateAdded']) : null,
                'dateUpdated' => isset($data["contact"]['dateUpdated']) ? new DateTime($data["contact"]['dateUpdated']) : null,
                'dateOfBirth' => isset($data["contact"]['dateUpdated']) ? new DateTime($data["contact"]['dateUpdated']) : null,
                'lastActivity' => isset($data["contact"]['lastActivity']) ? $data["contact"]['lastActivity'] : null,
            ]);

            if (isset($data["contact"]['tags'])) {
                foreach ($data["contact"]['tags'] as $tag) {
                    ContactTag::create([
                        'value' => $tag,
                        'contact_id' => $newContact->id
                    ]);
                }
            }

            if (isset($data["contact"]['customField'])) {
                foreach ($data["contact"]['customField'] as $customField) {
                    ContactCustomField::create([
                        'customFieldId' => $customField['id'],
                        'value' => $customField['value'],
                        'contact_id' => $newContact->id
                    ]);
                }
            }

            DB::commit();

            return $newContact;
        }

        return null;
    }
}
