<?php

namespace App\Console\Commands;

use App\Models\Job;
use App\Models\SalesPerson;
use App\Services\APIClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class syncSalesPerson extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:salesPerson';
    protected $client;
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs table SalesPerson with users api';

    public function __construct(APIClient $apiClient)
    {
        parent::__construct();
        $this->client = $apiClient;
    }

    /**
     * 1. Deletes SalesPersons table
     * 2. Call gohighlevel api (users)
     * 3. Populate SalesPersons table
     */
    public function handle()
    {
        $data = $this->client->get("https://rest.gohighlevel.com/v1/users/");

        if ($data["users"]) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            SalesPerson::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            DB::beginTransaction();
            foreach ($data["users"] as $user) {
                SalesPerson::create([
                    'assignedUserId' => isset($user['id']) ? $user['id'] : null,
                    'name' => isset($user['name']) ? $user['name'] : null,
                    'firstName' => isset($user['firstName']) ? $user['firstName'] : null,
                    'lastName' => isset($user['lastName']) ? $user['lastName'] : null,
                    'email' => isset($user['email']) ? $user['email'] : null,
                    'phone' => isset($user['phone']) ? $user['phone'] : null,
                    'extension' => isset($user['extension']) ? $user['extension'] : null,
                ]);
            }
            DB::commit();

            Job::create([
                'jobresult' => 'SUCCESS',
                'jobname' => 'sync:salesPerson'
            ]);

            return Command::SUCCESS;
        }

        Job::create([
            'jobresult' => 'FAILURE',
            'jobname' => 'sync:salesPerson'
        ]);

        return Command::FAILURE;
    }
}
