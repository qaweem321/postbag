<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CampaignResource;
use App\Models\Campaign;
use App\Services\CampaignStats;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CampaignController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return CampaignResource::collection(Campaign::query()->with('mailingList')->latest('id')->paginate(25));
    }

    public function show(Campaign $campaign, CampaignStats $stats): CampaignResource
    {
        return (new CampaignResource($campaign->load('mailingList')))->additional(['stats' => $stats->for($campaign)]);
    }
}
