<?php

namespace App\Console\Commands;

use DateTime;
use App\Models\Opportunity;
use App\Services\APIClient;
use App\Models\OpportunityTag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class syncOpportunity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:opportunity';
    protected $client;
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs table opportunities with pipelines/opportunities api';

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
        Opportunity::truncate();

        $result = $this->sync("https://rest.gohighlevel.com/v1/pipelines/nIcczYbNQA6WLkFkbHrb/opportunities?limit=100&stageId=65bc9a79-6f5c-487d-b91d-bf8729a0d8ee");

        return $result ? Command::SUCCESS : Command::FAILURE;
    }

    public function sync($url)
    {
        $data = $this->client->get($url);

        if ($data) {
            DB::beginTransaction();

            foreach ($data["opportunities"] as $opportunity) {
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
            if (count($data["opportunities"]) > 0) {
                echo $data["meta"]["nextPageUrl"] . "\n";
                $this->sync($data["meta"]["nextPageUrl"]);
            }
            return true;
        }
        return false;
    }
}
