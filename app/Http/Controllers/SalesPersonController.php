<?php

namespace App\Http\Controllers;

use App\Models\SalesPerson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesPersonController extends Controller
{
    function sync()
    {
        $data = $this->callAPI("https://rest.gohighlevel.com/v1/users/");

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

            return response()->json(['data' => 'Sales Persons Synced'], 200);
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
