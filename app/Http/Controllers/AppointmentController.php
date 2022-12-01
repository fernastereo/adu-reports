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
use App\Models\AppointmentTag;
use App\Models\ContactCustomField;
use Illuminate\Support\Facades\DB;
use App\Models\AppointmentAttachment;
use App\Models\AppointmentCustomField;
use Google\Service\Sheets;

class AppointmentController extends Controller
{
    public function appointmentReport($startDate, $endDate)
    {
        try {
            //code...
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
                        $meetingFeedback = $filtered_arr[$key]["value"];
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

    function exportData($data = 'information society')
    {

        try {
            $sheets = Sheets::sheet('RawData')->append([['3', 'name3', 'mail3']]);

            return response()->json(
                [
                    'success' => false,
                    'result' => 'exported',
                    'data' => $sheets,
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
                    'result' => 'exported',
                    'data' => '',
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

    function sync($calendar)
    {
        $mbs = config('constants.months_before_to_sync');
        $mas = config('constants.months_after_to_sync');
        $calendarId = config("constants.$calendar");
        $endDate = date('Y-m-d'); //today

        $startDate = date("Y-m-d", strtotime($endDate . "-$mbs months")); //n months before today
        $endDate = date("Y-m-d", strtotime($endDate . "+$mas months")); //n months after today

        //convert to epoch timestamp format
        $startDate = str_pad(strval(strtotime($startDate)), 13, "0", STR_PAD_RIGHT);
        $endDate = str_pad(strval(strtotime($endDate)), 13, "0", STR_PAD_RIGHT);

        $data = $this->callAPI("https://rest.gohighlevel.com/v1/appointments/?startDate=$startDate&endDate=$endDate&calendarId=$calendarId&includeAll=true");

        if ($data["appointments"]) {
            //clean appointments tables just when is callMeetingCalendarId
            if ($calendar === 'callMeetingCalendarId') {
                Appointment::truncate();
                AppointmentAttachment::truncate();
                AppointmentCustomField::truncate();
                AppointmentTag::truncate();
            }

            DB::beginTransaction();
            foreach ($data["appointments"] as $appointment) {
                $newAppointment = Appointment::create([
                    'appointmentid' => isset($appointment['id']) ? $appointment['id'] : null,
                    'selectedTimezone' => isset($appointment['selectedTimezone']) ? $appointment['selectedTimezone'] : null,
                    'notes' => isset($appointment['notes']) ? $appointment['notes'] : null,
                    'contactId' => isset($appointment['contactId']) ? $appointment['contactId'] : null,
                    'locationId' => isset($appointment['locationId']) ? $appointment['locationId'] : null,
                    'isFree' => isset($appointment['isFree']) ? $appointment['isFree'] : null,
                    'title' => isset($appointment['title']) ? $appointment['title'] : null,
                    'isRecurring' => isset($appointment['isRecurring']) ? $appointment['isRecurring'] : null,
                    'address' => isset($appointment['address']) ? $appointment['address'] : null,
                    'assignedUserId' => isset($appointment['assignedUserId']) ? $appointment['assignedUserId'] : null,
                    'calendarId' => isset($appointment['calendarId']) ? $appointment['calendarId'] : null,
                    'appoinmentStatus' => isset($appointment['appoinmentStatus']) ? $appointment['appoinmentStatus'] : null,
                    'calendarProviderId' => isset($appointment['calendarProviderId']) ? $appointment['calendarProviderId'] : null,
                    'userCalendarId' => isset($appointment['userCalendarId']) ? $appointment['userCalendarId'] : null,
                    'status' => isset($appointment['status']) ? $appointment['status'] : null,
                    'appointmentStatus' => isset($appointment['appointmentStatus']) ? $appointment['appointmentStatus'] : null,
                    'appointmentstartTime' => isset($appointment['startTime']) ? new DateTime($appointment['startTime']) : null,
                    'appointmentendTime' => isset($appointment['endTime']) ? new DateTime($appointment['endTime']) : null,
                    'appointmentcreatedAt' => isset($appointment['createdAt']) ? new DateTime($appointment['createdAt']) : null,
                    'appointmentupdatedAt' => isset($appointment['updatedAt']) ? new DateTime($appointment['updatedAt']) : null,
                    'contactfirstName' => isset($appointment['contact']['firstName']) ? $appointment['contact']['firstName'] : null,
                    'contactemail' => isset($appointment['contact']['email']) ? $appointment['contact']['email'] : null,
                    'contactfingerprint' => isset($appointment['contact']['fingerprint']) ? $appointment['contact']['fingerprint'] : null,
                    'contactfirstNameLowerCase' => isset($appointment['contact']['firstNameLowerCase']) ? $appointment['contact']['firstNameLowerCase'] : null,
                    'contactfullNameLowerCase' => isset($appointment['contact']['fullNameLowerCase']) ? $appointment['contact']['fullNameLowerCase'] : null,
                    'contacttimezone' => isset($appointment['contact']['timezone']) ? $appointment['contact']['timezone'] : null,
                    'contactemailLowerCase' => isset($appointment['contact']['emailLowerCase']) ? $appointment['contact']['emailLowerCase'] : null,
                    'contactlastName' => isset($appointment['contact']['lastName']) ? $appointment['contact']['lastName'] : null,
                    'contactlocationId' => isset($appointment['contact']['locationId']) ? $appointment['contact']['locationId'] : null,
                    'contactcountry' => isset($appointment['contact']['country']) ? $appointment['contact']['country'] : null,
                    'contactphone' => isset($appointment['contact']['phone']) ? $appointment['contact']['phone'] : null,
                    'contactlastNameLowerCase' => isset($appointment['contact']['lastNameLowerCase']) ? $appointment['contact']['lastNameLowerCase'] : null,
                    'contacttype' => isset($appointment['contact']['type']) ? $appointment['contact']['type'] : null,
                    'contactdateAdded' => isset($appointment['contact']['dateAdded']) ? new DateTime($appointment['contact']['dateAdded']) : null,
                    'contactpostalCode' => isset($appointment['contact']['postalCode']) ? $appointment['contact']['postalCode'] : null,
                    'contactsource' => isset($appointment['contact']['source']) ? $appointment['contact']['source'] : null,
                ]);

                if (isset($appointment['contact']['tags'])) {
                    foreach ($appointment['contact']['tags'] as $tag) {
                        AppointmentTag::create([
                            'value' => $tag,
                            'appointment_id' => $newAppointment->id
                        ]);
                    }
                }

                if (isset($appointment['contact']['attachments'])) {
                    foreach ($appointment['contact']['attachments'] as $attachment) {
                        AppointmentAttachment::create([
                            'value' => $attachment,
                            'appointment_id' => $newAppointment->id
                        ]);
                    }
                }
            }
            DB::commit();

            return response()->json(['data' => 'Appointments Synced'], 200);
        }

        return response()->json(['data' => 'Something went wrong'], 200);
    }

    function syncContact($contactId)
    {
        ini_set('max_execution_time', 360);
        $data = $this->callAPI("https://rest.gohighlevel.com/v1/contacts/$contactId");

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

    private function callAPI($url)
    {
        try {
            //code...
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer " . config('constants.token_api')
                ],
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                return "cURL Error #:" . $err;
            } else {
                return json_decode($response, true);;
            }
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }
}
