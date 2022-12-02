<?php

namespace App\Console\Commands;

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
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $data = $this->client->get("https://rest.gohighlevel.com/v1/users/");

        if ($data["users"]) {
            SalesPerson::truncate();

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

            return Command::SUCCESS;
        }

        return Command::FAILURE;
    }
}