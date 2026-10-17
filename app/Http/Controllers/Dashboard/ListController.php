<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\SubscriptionStatus;
use App\Exceptions\SuppressedAddress;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListRequest;
use App\Models\MailingList;
use App\Models\Subscription;
use App\Services\SubscriptionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ListController extends Controller
{
    public function index(): View
    {
        return view('lists.index', [
            'lists' => MailingList::query()
                ->withCount(['activeSubscriptions', 'subscriptions as pending_count' => fn ($q) => $q->where('status', SubscriptionStatus::Pending)])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('lists.form', ['list' => new MailingList(['double_opt_in' => true])]);
    }

    public function store(ListRequest $request): RedirectResponse
    {
        $list = MailingList::query()->create($request->validated());

        return redirect()->route('lists.show', $list)->with('status', 'List created.');
    }

    public function show(Request $request, MailingList $list): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:254'],
            'status' => ['nullable', Rule::enum(SubscriptionStatus::class)],
        ]);

        $subscriptions = $list->subscriptions()
            ->with('subscriber')
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['q'] ?? null, fn ($q, $search) => $q->whereRelation('subscriber', 'email', 'like', '%'.addcslashes(mb_strtolower($search), '%_\\').'%'))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('lists.show', [
            'list' => $list->loadCount('activeSubscriptions'),
            'subscriptions' => $subscriptions,
            'filters' => $filters,
        ]);
    }

    public function edit(MailingList $list): View
    {
        return view('lists.form', ['list' => $list]);
    }

    public function update(ListRequest $request, MailingList $list): RedirectResponse
    {
        $list->update($request->validated());

        return redirect()->route('lists.show', $list)->with('status', 'List saved.');
    }

    public function destroy(MailingList $list): RedirectResponse
    {
        if ($list->campaigns()->exists()) {
            return back()->withErrors(['list' => 'This list has campaigns. Delete them first, or keep the list for their history.']);
        }
        $list->delete();

        return redirect()->route('lists.index')->with('status', "List \"{$list->name}\" deleted.");
    }

    public function addSubscriber(Request $request, MailingList $list, SubscriptionManager $subscriptions): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'name' => ['nullable', 'string', 'max:120'],
            'skip_confirmation' => ['boolean'],
        ]);
        try {
            $outcome = $subscriptions->subscribe($list, $data['email'], $data['name'] ?? null, 'dashboard', $request->boolean('skip_confirmation'));
        } catch (SuppressedAddress $error) {
            return back()->withErrors(['email' => $error->getMessage()])->withInput();
        }

        return back()->with('status', match ($outcome->value) {
            'subscribed' => "{$data['email']} is subscribed.",
            'pending_confirmation' => "We sent {$data['email']} a confirmation email.",
            default => "{$data['email']} was already subscribed.",
        });
    }

    public function unsubscribe(MailingList $list, Subscription $subscription, SubscriptionManager $subscriptions): RedirectResponse
    {
        abort_unless($subscription->mailing_list_id === $list->id, 404);
        $subscriptions->unsubscribe($subscription);

        return back()->with('status', "{$subscription->loadMissing('subscriber')->subscriber->email} is unsubscribed.");
    }
}
