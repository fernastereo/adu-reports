<?php

namespace App\Http\Controllers;

use DateTime;
use App\Models\Appointment;
use Illuminate\Http\Request;
use App\Models\AppointmentTag;
use Illuminate\Support\Facades\DB;
use App\Models\AppointmentAttachment;
use App\Models\AppointmentCustomField;

class AppointmentController extends Controller
{
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
