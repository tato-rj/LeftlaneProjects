@if($value)
    @php($time = \Carbon\CarbonImmutable::parse($value, 'UTC')->setTimezone(config('app.timezone')))
    <time datetime="{{ $time->toIso8601String() }}" title="{{ $time->format('M j, Y, g:i:s a T') }}">{{ $time->format('M j, g:i:s a T') }}</time>
@else
    {{ $empty ?? '—' }}
@endif
