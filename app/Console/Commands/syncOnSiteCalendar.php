<?php

namespace App\Console\Commands;

use DateTime;
use App\Models\Job;
use App\Models\Appointment;
use App\Services\APIClient;
use App\Models\AppointmentTag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\AppointmentAttachment;
use App\Models\AppointmentCustomField;

class syncOnSiteCalendar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:onSiteCalendar';
    protected $client;
    /**
     * 1. This command should be executed after syncCallMeetingCalendar command 
     * (Never run it before because data will be lost when you run syncCallMeetingCalendar)
     * 2. Call gohighlevel api n months before and 1 month after current date (see .env file)
     * 3. Populate Appointments tables with OnSiteCalendar
     *
     * @var string
     */
    protected $description = 'Syncs table Appointment with appointments api (onSiteEvaluation calendar)';

    public function __construct(APIClient $apiClient)
    {
        parent::__construct();
        $this->client = $apiClient;
    }
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $mbs = config('constants.months_before_to_sync');
        $mas = config('constants.months_after_to_sync');
        $calendarId = config("constants.onSiteEvaluationCalendarId");
        $endDate = date('Y-m-d'); //today

        $startDate = date("Y-m-d", strtotime($endDate . "-$mbs months")); //n months before today
        $endDate = date("Y-m-d", strtotime($endDate . "+$mas months")); //n months after today

        //convert to epoch timestamp format
        $startDate = str_pad(strval(strtotime($startDate)), 13, "0", STR_PAD_RIGHT);
        $endDate = str_pad(strval(strtotime($endDate)), 13, "0", STR_PAD_RIGHT);

        $data = $this->client->get("https://rest.gohighlevel.com/v1/appointments/?startDate=$startDate&endDate=$endDate&calendarId=$calendarId&includeAll=true");

        if ($data["appointments"]) {
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

            Job::create([
                'jobresult' => 'SUCCESS',
                'jobname' => 'sync:onSiteCalendar'
            ]);

            return Command::SUCCESS;
        }

        Job::create([
            'jobresult' => 'FAILURE',
            'jobname' => 'sync:onSiteCalendar'
        ]);

        return Command::FAILURE;
    }
}
