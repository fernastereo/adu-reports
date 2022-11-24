<?php

namespace App\Http\Controllers;

use DateTime;
use App\Models\Contact;
use App\Models\ContactTag;
use Illuminate\Http\Request;
use App\Models\ContactCustomField;
use Illuminate\Support\Facades\DB;

class ContactController extends Controller
{
    function sync()
    {
        ini_set('max_execution_time', 360);
        $data = $this->callAPI("https://rest.gohighlevel.com/v1/contacts?limit=100");

        if ($data["contacts"]) {
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
                        ContactCustomField::create([
                            'customFieldId' => $customField['id'],
                            'value' => $customField['value'],
                            'contact_id' => $newContact->id
                        ]);
                    }
                }
            }
            DB::commit();

            return response()->json(['data' => 'Contacts Synced'], 200);
        }

        return response()->json(['data' => 'Something went wrong'], 200);
    }

    private function callAPI($url, $previousResponse = [])
    {
        try {
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => -1,
                CURLOPT_TIMEOUT => 300,
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
                return json_decode($response, true);
            }
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }
}
