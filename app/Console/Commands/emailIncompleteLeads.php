<?php

namespace App\Console\Commands;

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
                    'html' => $preparedData['reportData']
                ];

                //Call Mailgun API
                $res = $this->client->sendEmail($url, $data);
                echo "llamó al api" . PHP_EOL;
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
                        'to' => 'fernandoecueto@gmail.com',
                        'cc' => config('mail.send_reports_to'),
                        'subject' => $person->name . ' :Leads without Disposition and Feedback',
                        'html' => $preparedData['reportData']
                    ];

                    //Call Mailgun API
                    $res = $this->client->sendEmail($url, $data);
                    echo "llamó al api y ";
                    if (count($res) > 0) {
                        echo "Report sent to " . $data["to"] . PHP_EOL;
                    }
                }
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function prepareData($data, $all, $person = null)
    {
        $result = $all
            ? "<html><h3>[GENERAL REPORT] Leads without disposition or sales feedback set from {$data['params']['startDate']} to {$data['params']['endDate']}</h3><table class='blueTable'><thead><tr>"
            : "<html><h3>{$person->name}: Leads without disposition or sales feedback set from {$data['params']['startDate']} to {$data['params']['endDate']}</h3><table class='blueTable'><thead><tr>";

        $result .= "<th>Date</th>
                        <th>Customer Name</th>
                        <th>Sales Person</th>
                        <th>Call Meeting</th>
                        <th>On Site</th>
                    </tr></thead><tbody>";

        $dataToSend = false;
        foreach ($data["data"] as $lead) {
            if ($all) {
                if ($lead["disposition"] === '' || $lead["salesPersonFeedback"] === '') {
                    $result .= "<tr><td>" . date("Y-m-d", strtotime($lead['date'])) . "</td><td>" . $lead['customerName'] . "</td><td>" . $lead['salesPerson'] . "</td>
                        <td>" . $lead['callMeeting'] . "</td><td>" . $lead['onSite'] . "</td></tr>";

                    $dataToSend = true;
                }
            } else {
                if ($person->assignedUserId === $lead["assignedUserId"] && ($lead["disposition"] === '' || $lead["salesPersonFeedback"] === '')) {
                    $result .= "<tr><td>" . date("Y-m-d", strtotime($lead['date'])) . "</td><td>" . $lead['customerName'] . "</td><td>" . $lead['salesPerson'] . "</td>
                        <td>" . $lead['callMeeting'] . "</td><td>" . $lead['onSite'] . "</td></tr>";

                    $dataToSend = true;
                }
            }
        }
        $result .= "</tbody></table></html>";

        return ['dataToSend' => $dataToSend, 'result' => $result];
    }
}
