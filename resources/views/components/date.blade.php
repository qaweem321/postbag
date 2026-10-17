@props(['value', 'time' => false])
{{-- Unambiguous dates in UTC: "Oct 11, 2026" (and "14:05 UTC" when the time matters). --}}
@if ($value)
<time datetime="{{ $value->toIso8601String() }}" title="{{ $value->utc()->format('M j, Y, H:i') }} UTC">{{ $value->utc()->format($time ? 'M j, Y, H:i \U\T\C' : 'M j, Y') }}</time>
@else
<span class="muted">–</span>
@endif
