@props(['status'])
@php
    $value = $status instanceof BackedEnum ? $status->value : (string) $status;
    $style = [
        'subscribed' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300', 'sent' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300', 'active' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
        'pending' => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300', 'scheduled' => 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300', 'sending' => 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300',
        'unsubscribed' => 'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300', 'draft' => 'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300', 'cancelled' => 'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
        'bounced' => 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300', 'complained' => 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300', 'failed' => 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
    ][$value] ?? 'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300';
@endphp
<span {{ $attributes->class("inline-flex rounded-full px-2 py-0.5 text-xs font-medium $style") }}>{{ ucfirst($value) }}</span>
