<?php

namespace App\Console\Commands;

use App\Models\Job;
use App\Models\SalesPerson;
use App\Services\APIClient;
use Illuminate\Http\Request;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\View;

class emailIncompleteLeads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:incompleteleads';
    protected $client;
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send an email to every sales person and admin with the leads without disposition and salesFeedback fields';

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
        try {
            $mbs = config('constants.days_before_start_send_email_incomplete');
            $mas = config('constants.days_before_end_send_email_incomplete');

            $endDate = date('Y-m-d'); //today

            $startDate = date("Y-m-d", strtotime($endDate . "-$mbs days")); //n days before today
            $endDate = date("Y-m-d", strtotime($endDate . "-$mas days")); //n days before today

            $request = Request::create("api/reports/appointmentreport/$startDate/$endDate", 'GET');

            $response = app()->handle($request);

            $report = json_decode($response->getContent(), true);

            $url = "https://api.mailgun.net/v3/" . config('services.mailgun.domain') . "/messages";

            //preparar los datos para el reporte general
            $preparedData = $this->prepareData($report, true);

            if ($preparedData['dataToSend']) {
                $data = [
                    'from' => config('mail.from.name') . '<' . config('mail.from.address') . '>',
                    'to' => config('mail.send_reports_to'),
                    'subject' => '[GENERAL REPORT] Leads without Disposition and Feedback',
                    'html' => $preparedData['result']
                ];

                //Call Mailgun API
                $res = $this->client->sendEmail($url, $data);

                if (count($res) > 0) {
                    echo "Report sent to " . $data["to"] . PHP_EOL;
                }
            }

            $salesPersons = SalesPerson::all();
            foreach ($salesPersons as $person) {
                //preparar los datos para el reporte de cada salesPerson
                $preparedData = $this->prepareData($report, false, $person);

                if ($preparedData['dataToSend'] > 0) {
                    $data = [
                        'from' => config('mail.from.name') . '<' . config('mail.from.address') . '>',
                        'to' => $person->email,
                        'subject' => $person->name . ' :Leads without Disposition and Feedback',
                        'html' => $preparedData['result']
                    ];

                    //Call Mailgun API
                    $res = $this->client->sendEmail($url, $data);

                    if (count($res) > 0) {
                        echo "Report sent to " . $person->email . PHP_EOL;
                    }
                }
            }

            Job::create([
                'jobresult' => 'SUCCESS',
                'jobname' => 'email:incompleteleads'
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {

            Job::create([
                'jobresult' => 'FAILURE',
                'jobname' => 'email:incompleteleads'
            ]);

            echo $e->getMessage();

            return Command::FAILURE;
        }
    }

    public function prepareData($data, $all, $person = null)
    {
        $result = $all
            ? "<html><h3>[GENERAL REPORT] Leads without disposition or sales feedback set from {$data['params']['startDate']} to {$data['params']['endDate']}</h3>"
            : "<html><h3>{$person->name}: Leads without disposition or sales feedback set from {$data['params']['startDate']} to {$data['params']['endDate']}</h3>";

        $result .= "<table style='border: 1px solid #919BA4; background-color: #EEEEEE; width: 100%; text-align: left; border-collapse: collapse;'>
                        <thead style='background: #969C9E; border-bottom: 2px solid #444444;'>
                            <tr>
                                <th style=' width: 80px; border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px; font-weight: bold; color: #0A0A0A; border-left: 2px solid #444444;'>Date</th>
                                <th style=' width: 150px; border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px; font-weight: bold; color: #0A0A0A; border-left: 2px solid #444444;'>Customer Name</th>
                                <th style=' width: 150px; border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px; font-weight: bold; color: #0A0A0A; border-left: 2px solid #444444;'>Sales Person</th>
                                <th style=' width: 90px; border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px; font-weight: bold; color: #0A0A0A; border-left: 2px solid #444444;'>Call Meeting</th>
                                <th style=' width: 90px; border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px; font-weight: bold; color: #0A0A0A; border-left: 2px solid #444444;'>On Site</th>
                                <th style=' width: 250px; border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px; font-weight: bold; color: #0A0A0A; border-left: 2px solid #444444;'>Disposition</th>
                                <th style=' width: 250px; border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px; font-weight: bold; color: #0A0A0A; border-left: 2px solid #444444;'>Sales Person Feedback</th>
                            </tr>
                        </thead>
                        <tbody>";

        $dataToSend = false;
        foreach ($data["data"] as $lead) {
            if ($all) {
                if ($lead["disposition"] === '' || $lead["salesPersonFeedback"] === '') {
                    $result .= "<tr>
                        <td style='border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px;'>" . date("Y-m-d", strtotime($lead['date'])) . "</td>
                        <td style='border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px;'>" . $lead['customerName'] . "</td>
                        <td style='border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px;'>" . $lead['salesPerson'] . "</td>
                        <td style='border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px;'>" . $lead['callMeeting'] . "</td>
                        <td style='border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px;'>" . $lead['onSite'] . "</td>
                        <td style='border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px;'>" . $lead['disposition'] . "</td>
                        <td style='border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px;'>" . $lead['salesPersonFeedback'] . "</td>
                    </tr>";

                    $dataToSend = true;
                }
            } else {
                if ($person->assignedUserId === $lead["assignedUserId"] && ($lead["disposition"] === '' || $lead["salesPersonFeedback"] === '')) {
                    $result .= "<tr>
                        <td style='border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px;'>" . date("Y-m-d", strtotime($lead['date'])) . "</td>
                        <td style='border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px;'>" . $lead['customerName'] . "</td>
                        <td style='border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px;'>" . $lead['salesPerson'] . "</td>
                        <td style='border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px;'>" . $lead['callMeeting'] . "</td>
                        <td style='border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px;'>" . $lead['onSite'] . "</td>
                        <td style='border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px;'>" . $lead['disposition'] . "</td>
                        <td style='border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px;'>" . $lead['salesPersonFeedback'] . "</td>
                    </tr>";

                    $dataToSend = true;
                }
            }
        }
        $result .= "</tbody></table></html>";

        return ['dataToSend' => $dataToSend, 'result' => $result];
    }
}
