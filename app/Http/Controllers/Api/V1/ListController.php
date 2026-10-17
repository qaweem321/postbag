<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MailingListResource;
use App\Models\MailingList;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return MailingListResource::collection(MailingList::query()->withCount('activeSubscriptions')->orderBy('name')->get());
    }

    public function show(MailingList $list): MailingListResource
    {
        return new MailingListResource($list->loadCount('activeSubscriptions'));
    }
}
