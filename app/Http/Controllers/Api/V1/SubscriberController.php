<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SubscribeOutcome;
use App\Enums\SubscriberStatus;
use App\Enums\SubscriptionStatus;
use App\Exceptions\SuppressedAddress;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubscribeRequest;
use App\Http\Resources\SubscriptionResource;
use App\Models\MailingList;
use App\Models\Subscriber;
use App\Models\Subscription;
use App\Services\SubscriptionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class SubscriberController extends Controller
{
    public function __construct(private SubscriptionManager $subscriptions) {}

    /** Cursor pagination: stable while people subscribe, and fast at any depth. */
    public function index(Request $request, MailingList $list): AnonymousResourceCollection
    {
        $request->validate([
            'status' => ['nullable', Rule::enum(SubscriptionStatus::class)],
            'per_page' => ['nullable', 'integer', 'between:1,200'],
        ]);

        $page = $list->subscriptions()
            ->with('subscriber')
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->orderBy('id')
            ->cursorPaginate((int) $request->query('per_page', 50));

        return SubscriptionResource::collection($page);
    }

    public function store(SubscribeRequest $request, MailingList $list): JsonResponse
    {
        try {
            $outcome = $this->subscriptions->subscribe(
                $list,
                $request->string('email')->toString(),
                $request->input('name'),
                $request->input('source', 'api'),
                $request->boolean('skip_confirmation'),
            );
        } catch (SuppressedAddress $error) {
            return response()->json(['message' => $error->getMessage()], 422);
        }

        $subscription = $this->find($list, $request->string('email')->toString());

        return (new SubscriptionResource($subscription->load('mailingList')))
            ->additional(['outcome' => $outcome->value])
            ->response()
            ->setStatusCode($outcome === SubscribeOutcome::AlreadySubscribed ? 200 : 201);
    }

    public function show(MailingList $list, string $email): SubscriptionResource
    {
        return new SubscriptionResource($this->find($list, $email)->load('mailingList'));
    }

    public function destroy(MailingList $list, string $email): SubscriptionResource
    {
        $subscription = $this->find($list, $email);
        $this->subscriptions->unsubscribe($subscription);

        return new SubscriptionResource($subscription->load('mailingList'));
    }

    /** Records a bounce or spam complaint reported by the mail provider. */
    public function suppress(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'reason' => ['required', Rule::in([SubscriberStatus::Bounced->value, SubscriberStatus::Complained->value])],
        ]);
        $subscriber = $this->subscriptions->suppress($data['email'], SubscriberStatus::from($data['reason']));

        return response()->json(['data' => ['email' => $subscriber->email, 'status' => $subscriber->status->value]]);
    }

    private function find(MailingList $list, string $email): Subscription
    {
        return Subscription::query()
            ->where('mailing_list_id', $list->id)
            ->whereRelation('subscriber', 'email', Subscriber::normalizeEmail($email))
            ->with('subscriber')
            ->firstOrFail();
    }
}
