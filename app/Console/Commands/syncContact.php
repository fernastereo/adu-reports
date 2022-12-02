<?php

namespace App\Console\Commands;

use DateTime;
use App\Models\Contact;
use App\Models\ContactTag;
use App\Services\APIClient;
use Illuminate\Console\Command;
use App\Models\ContactCustomField;
use Illuminate\Support\Facades\DB;

class syncContact extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:contact';
    protected $client;
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs table contacts with contacts api';

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
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Contact::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $result = $this->sync("https://rest.gohighlevel.com/v1/contacts?limit=100");

        return $result ? Command::SUCCESS : Command::FAILURE;
    }

    function sync($url)
    {
        // ini_set('max_execution_time', 360);
        $data = $this->client->get($url);

        if (count($data["contacts"]) > 0) {
            DB::beginTransaction();

            foreach ($data["contacts"] as $contact) {
                $newContact = Contact::create([
                    'contactid' => isset($contact['id']) ? $contact['id'] : null,
                    'locationId' => isset($contact['locationId']) ? $contact['locationId'] : null,
                    'contactName' => isset($contact['contactName']) ? $contact['contactName'] : null,
                    'firstName' => isset($contact['firstName']) ? $contact['firstName'] : null,
                    'lastName' => isset($contact['lastName']) ? $contact['lastName'] : null,
                    'companyName' => isset($contact['companyName']) ? $contact['companyName'] : null,
                    'email' => isset($contact['email']) ? $contact['email'] : null,
                    'phone' => isset($contact['phone']) ? $contact['phone'] : null,
                    'dnd' => isset($contact['dnd']) ? $contact['dnd'] : null,
                    'type' => isset($contact['type']) ? $contact['type'] : null,
                    'source' => isset($contact['source']) ? $contact['source'] : null,
                    'assignedTo' => isset($contact['assignedTo']) ? $contact['assignedTo'] : null,
                    'city' => isset($contact['city']) ? $contact['city'] : null,
                    'state' => isset($contact['state']) ? $contact['state'] : null,
                    'postalCode' => isset($contact['postalCode']) ? $contact['postalCode'] : null,
                    'address1' => isset($contact['address1']) ? $contact['address1'] : null,
                    'dateAdded' => isset($contact['dateAdded']) ? new DateTime($contact['dateAdded']) : null,
                    'dateUpdated' => isset($contact['dateUpdated']) ? new DateTime($contact['dateUpdated']) : null,
                    'dateOfBirth' => isset($contact['dateUpdated']) ? new DateTime($contact['dateUpdated']) : null,
                    'lastActivity' => isset($contact['lastActivity']) ? $contact['lastActivity'] : null,
                ]);

                if (isset($contact['tags'])) {
                    foreach ($contact['tags'] as $tag) {
                        ContactTag::create([
                            'value' => $tag,
                            'contact_id' => $newContact->id
                        ]);
                    }
                }

                if (isset($contact['customField'])) {
                    foreach ($contact['customField'] as $customField) {
                        try {
                            $value = is_string($customField['value']) ? $customField['value'] : $customField['value'][0];
                            ContactCustomField::create([
                                'customFieldId' => $customField['id'],
                                'value' => $value,
                                'contact_id' => $newContact->id
                            ]);
                        } catch (\Throwable $th) {
                            //throw $th;
                        }
                    }
                }
            }
            DB::commit();
            if (count($data["contacts"]) > 0) {
                echo $data["meta"]["nextPageUrl"] . "\n";
                $this->sync($data["meta"]["nextPageUrl"]);
            }
            return true;
        }

        return false;
    }
}
