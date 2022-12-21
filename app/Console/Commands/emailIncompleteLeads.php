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
        $result = "<html>";
        $result .= $all
            ? "<h3>[GENERAL REPORT] Leads without disposition or sales feedback set from {$data['params']['startDate']} to {$data['params']['endDate']}</h3>"
            : "<h3>{$person->name}: Leads without disposition or sales feedback set from {$data['params']['startDate']} to {$data['params']['endDate']}</h3>";

        $result .= "<table style='border: 1px solid #b53c00; background-color: #EEEEEE; width: 100%; text-align: left; border-collapse: collapse;'>
                        <thead style='background: #1775c9; border-bottom: 2px solid #ffffff;'>
                            <tr>
                                <th style=' width: 80px; border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px; font-weight: bold; color: #ffffff; border-left: 2px solid #444444; text-align: center;'>Date</th>
                                <th style=' width: 150px; border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px; font-weight: bold; color: #ffffff; border-left: 2px solid #444444; text-align: center;'>Customer Name</th>
                                <th style=' width: 150px; border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px; font-weight: bold; color: #ffffff; border-left: 2px solid #444444; text-align: center;'>Sales Person</th>
                                <th style=' width: 90px; border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px; font-weight: bold; color: #ffffff; border-left: 2px solid #444444; text-align: center;'>Call Meeting</th>
                                <th style=' width: 90px; border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px; font-weight: bold; color: #ffffff; border-left: 2px solid #444444; text-align: center;'>On Site</th>
                                <th style=' width: 250px; border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px; font-weight: bold; color: #ffffff; border-left: 2px solid #444444; text-align: center;'>Disposition</th>
                                <th style=' width: 250px; border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px; font-weight: bold; color: #ffffff; border-left: 2px solid #444444; text-align: center;'>Sales Person Feedback</th>
                            </tr>
                        </thead>
                        <tbody style='background-color: #ffffff; color: #000000;'>";

        $dataToSend = false;

        foreach ($data["data"] as $lead) {
            if ($all) {
                if ($lead["disposition"] === '' || $lead["salesPersonFeedback"] === '') {
                    $result .= $this->getRow($lead);
                    $dataToSend = true;
                }
            } else {
                if ($person->assignedUserId === $lead["assignedUserId"] && ($lead["disposition"] === '' || $lead["salesPersonFeedback"] === '')) {
                    $result .= $this->getRow($lead);
                    $dataToSend = true;
                }
            }
        }
        $result .= "</tbody></table></html>";

        return ['dataToSend' => $dataToSend, 'result' => $result];
    }

    function codeColor($record)
    {
        return ($record === 'showed'
            ? 'background-color: #bbf7d0; color: #40a061;'
            : ($record === 'confirmed'
                ? 'background-color: #bfdbfe; color: #5175f0;'
                : ($record === 'cancelled'
                    ? 'background-color: #fecaca; color: #dd4746;'
                    : '')));
    }

    function getRow($record)
    {
        $callMeetingColor = $this->codeColor($record['callMeeting']);
        $onSiteColor = $this->codeColor($record['onSite']);

        return "<tr>
                    <td style='border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px;'>" . date("Y-m-d", strtotime($record['date'])) . "</td>
                    <td style='border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px;'>" . $record['customerName'] . "</td>
                    <td style='border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px;'>" . $record['salesPerson'] . "</td>
                    <td style='border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px;'><p style='border-radius: 20px; $callMeetingColor padding: 5px; text-align: center;'>" . $record['callMeeting'] . "</p></td>
                    <td style='border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px;'><p style='border-radius: 20px; $onSiteColor padding: 5px; text-align: center;'>" . $record['onSite'] . "</p></td>
                    <td style='border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px;'>" . $record['disposition'] . "</td>
                    <td style='border: 1px solid #AAAAAA; padding: 3px 2px; font-size: 12px;'>" . $record['salesPersonFeedback'] . "</td>
                </tr>";
    }
}
