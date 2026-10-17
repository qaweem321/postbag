@props(['status'])
@php
    $value = $status instanceof BackedEnum ? $status->value : (string) $status;
    $style = [
        'subscribed' => 'bg-emerald-100 text-emerald-800', 'sent' => 'bg-emerald-100 text-emerald-800', 'active' => 'bg-emerald-100 text-emerald-800',
        'pending' => 'bg-amber-100 text-amber-800', 'scheduled' => 'bg-sky-100 text-sky-800', 'sending' => 'bg-sky-100 text-sky-800',
        'unsubscribed' => 'bg-zinc-200 text-zinc-700', 'draft' => 'bg-zinc-200 text-zinc-700', 'cancelled' => 'bg-zinc-200 text-zinc-700',
        'bounced' => 'bg-red-100 text-red-800', 'complained' => 'bg-red-100 text-red-800', 'failed' => 'bg-red-100 text-red-800',
    ][$value] ?? 'bg-zinc-200 text-zinc-700';
@endphp
<span {{ $attributes->class("inline-flex rounded-full px-2 py-0.5 text-xs font-medium $style") }}>{{ ucfirst($value) }}</span>
