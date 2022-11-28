<?php

namespace App\Http\Controllers;

use DateTime;
use App\Models\Opportunity;
use App\Models\OpportunityTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpportunityController extends Controller
{
    function sync()
    {
        $data = $this->callAPI("https://rest.gohighlevel.com/v1/pipelines/nIcczYbNQA6WLkFkbHrb/opportunities?limit=100&stageId=65bc9a79-6f5c-487d-b91d-bf8729a0d8ee");

        if ($data) {
            Opportunity::truncate();

            DB::beginTransaction();
            foreach ($data as $opportunity) {
                $newOpportunity = Opportunity::create([
                    'opportunityid' => isset($opportunity->id) ? $opportunity->id : null,
                    'name' => isset($opportunity->name) ? $opportunity->name : null,
                    'monetaryValue' => isset($opportunity->monetaryValue) ? $opportunity->monetaryValue : 0,
                    'pipelineId' => isset($opportunity->pipelineId) ? $opportunity->pipelineId : null,
                    'pipelineStageId' => isset($opportunity->pipelineStageId) ? $opportunity->pipelineStageId : null,
                    'pipelineStageUId' => isset($opportunity->pipelineStageUId) ? $opportunity->pipelineStageUId : null,
                    'assignedTo' => isset($opportunity->assignedTo) ? $opportunity->assignedTo : null,
                    'status' => isset($opportunity->status) ? $opportunity->status : null,
                    'lastStatusChangeAt' => isset($opportunity->lastStatusChangeAt) ? new DateTime($opportunity->lastStatusChangeAt) : null,
                    'createdAt' => isset($opportunity->createdAt) ? new DateTime($opportunity->createdAt) : null,
                    'updatedAt' => isset($opportunity->updatedAt) ? new DateTime($opportunity->updatedAt) : null,
                    'contactid' => isset($opportunity->contact->id) ? $opportunity->contact->id : null,
                    'contactname' => isset($opportunity->contact->name) ? $opportunity->contact->name : null,
                    'contactemail' => isset($opportunity->contact->email) ? $opportunity->contact->email : null,
                    'contactphone' => isset($opportunity->contact->phone) ? $opportunity->contact->phone : null,
                ]);

                if (isset($opportunity->contact->tags)) {
                    foreach ($opportunity->contact->tags as $tag) {
                        OpportunityTag::create([
                            'value' => $tag,
                            'opportunity_id' => $newOpportunity->id
                        ]);
                    }
                }
            }
            DB::commit();

            return response()->json(['data' => 'Opportunities Synced'], 200);
        }

        return response()->json(['data' => 'Something went wrong'], 200);
    }

    private function callAPI($url, $previousResponse = [])
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
                $newResponse = json_decode($response);
                $result = array_merge($previousResponse, $newResponse->opportunities);

                if (count($newResponse->opportunities) > 0) {
                    $url = $newResponse->meta->nextPageUrl;
                    return $this->callAPI($url, $result);
                }

                return $result;
            }
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }
}
