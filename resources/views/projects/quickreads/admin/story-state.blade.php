@if(($field ?? 'reading') === 'saved')
    <span class="badge activity-state {{ $row->saved === null ? 'unknown' : ($row->saved ? 'saved' : 'unsaved') }}">{{ $row->saved === null ? 'Not reported' : ($row->saved ? 'Saved' : 'Not saved') }}</span>
    @if($timestamps ?? true)<small class="activity-detail">@include('projects.quickreads.statistics.activity-time', ['value' => $row->saved_observed_at])</small>@endif
@else
    <span class="badge activity-state {{ $row->reading_status ?? 'unknown' }}">{{ ['reading' => 'Reading', 'finished' => 'Finished', 'not_started' => 'Not started'][$row->reading_status] ?? 'Not reported' }}</span>
    @if($timestamps ?? true)<small class="activity-detail">@include('projects.quickreads.statistics.activity-time', ['value' => $row->reading_observed_at])</small>@endif
@endif
