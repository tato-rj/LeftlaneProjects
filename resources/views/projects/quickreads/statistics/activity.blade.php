@extends('projects.quickreads.layouts.app')
@section('title', 'Story activity')
@section('content')
    @include('projects.quickreads.admin.heading', ['eyebrow' => 'THE READING PICTURE', 'heading' => 'Story activity', 'description' => 'See what readers have saved, are reading, and have finished.'])
    @php
        $reportUrl = fn ($changes) => url('/quickreads/story-activity').(($query = http_build_query(array_filter(array_replace($filters, ['page' => null], $changes), fn ($value) => $value !== null && $value !== ''))) ? '?'.$query : '');
    @endphp
    <div class="metric-grid activity-metrics">
        @foreach([['saved', 'Saved', 'bookmark'], ['reading', 'Reading', 'stories'], ['finished', 'Finished', 'check'], ['reports', 'Reader / story reports', 'users']] as [$key, $label, $icon])
            <a class="metric-card {{ ($filters['status'] ?? null) === $key ? 'is-selected' : '' }}" href="{{ $reportUrl(['status' => $key === 'reports' ? null : $key]) }}">
                <div class="metric-top"><span>{{ $label }}</span><span class="metric-icon">@include('projects.quickreads.admin.icon', ['name' => $icon])</span></div>
                <strong>{{ number_format($summary->$key) }}</strong>
                <span class="metric-link">{{ $key === 'reports' ? 'View all reports' : 'View '.$label }} @include('projects.quickreads.admin.icon', ['name' => 'arrow'])</span>
            </a>
        @endforeach
    </div>
    <form method="GET" class="filter-panel activity-filters">
        <input type="hidden" name="view" value="{{ $view }}">
        @foreach(['story_id', 'user_id'] as $identityFilter)
            @if(!empty($filters[$identityFilter]))<input type="hidden" name="{{ $identityFilter }}" value="{{ $filters[$identityFilter] }}">@endif
        @endforeach
        <div class="filter-bar">
            <div class="search-field">
                <label class="sr-only" for="activity-search">Search stories, authors or readers</label>
                @include('projects.quickreads.admin.icon', ['name' => 'search'])
                <input type="search" id="activity-search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search stories, authors or readers…" maxlength="150">
            </div>
            <div>
                <label class="sr-only" for="reader-kind">Readers</label>
                <select name="reader_kind" id="reader-kind">
                    <option value="">Guests and accounts</option>
                    <option value="guest" @selected(($filters['reader_kind'] ?? null) === 'guest')>Guests</option>
                    <option value="account" @selected(($filters['reader_kind'] ?? null) === 'account')>Accounts</option>
                </select>
            </div>
            <div>
                <label class="sr-only" for="activity-status">Status</label>
                <select name="status" id="activity-status">
                    <option value="">All states</option>
                    @foreach(['saved' => 'Saved', 'reading' => 'Reading', 'finished' => 'Finished', 'not_started' => 'Not started'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="sr-only" for="activity-provider">Sign-in method</label>
                <select id="activity-provider" name="provider">
                    <option value="">All sign-in methods</option>
                    <option value="apple" @selected(($filters['provider'] ?? '') === 'apple')>Apple accounts</option>
                    <option value="other" @selected(($filters['provider'] ?? '') === 'other')>Other accounts</option>
                </select>
            </div>
            <button type="submit" class="button primary">Refresh report</button>
            <a class="text-link" href="/quickreads/story-activity">Clear filters</a>
        </div>
    </form>
    @php
        $filterLabels = [
            'status' => ['saved' => 'Saved', 'reading' => 'Reading', 'finished' => 'Finished', 'not_started' => 'Not started'][$filters['status'] ?? ''] ?? null,
            'reader_kind' => ['guest' => 'Guests', 'account' => 'Accounts'][$filters['reader_kind'] ?? ''] ?? null,
            'provider' => ['apple' => 'Apple accounts', 'other' => 'Other accounts'][$filters['provider'] ?? ''] ?? null,
            'user_id' => empty($filters['user_id']) ? null : 'Reader #'.$filters['user_id'],
            'story_id' => empty($filters['story_id']) ? null : 'Story #'.$filters['story_id'],
            'q' => trim($filters['q'] ?? '') === '' ? null : 'Search: '.$filters['q'],
        ];
    @endphp
    @if(array_filter($filterLabels))
        <div class="active-filters" aria-label="Active filters"><strong>Showing:</strong>
            @foreach(array_filter($filterLabels) as $key => $label)<a href="{{ $reportUrl([$key => null]) }}" aria-label="Remove {{ $label }} filter">{{ $label }} <span aria-hidden="true">×</span></a>@endforeach
            <a href="/quickreads/story-activity">Show all activity</a>
        </div>
    @endif
    <div class="report-caption activity-caption">
        <span>Latest state update: @include('projects.quickreads.statistics.activity-time', ['value' => $summary->last_received_at, 'empty' => 'No reports yet'])</span>
        <span>Checked {{ now()->format('g:i:s a T') }} · {{ config('app.timezone') }}</span>
    </div>
    <section class="panel">
        <div class="panel-heading activity-report-heading">
            <div><h2>{{ $view === 'readers' ? 'Latest reader reports' : 'Totals by story' }}</h2><p>Current states for each reader and story. Saved can also be Reading or Finished.</p></div>
            <nav class="activity-views" aria-label="Activity view">
                <a href="{{ $reportUrl(['view' => 'readers']) }}" @if($view === 'readers') aria-current="page" @endif>Latest reports</a>
                <a href="{{ $reportUrl(['view' => 'stories']) }}" @if($view === 'stories') aria-current="page" @endif>By story</a>
            </nav>
        </div>
        <div class="table-scroll">
            <table class="story-activity-table">
                <thead><tr><th scope="col">Story</th><th scope="col">{{ $view === 'readers' ? 'Reader' : 'Readers' }}</th>
                    @if($view === 'readers')<th scope="col">Reading state</th><th scope="col">Saved state</th><th scope="col">State updated</th>
                    @else<th scope="col">Saved</th><th scope="col">Reading</th><th scope="col">Finished</th><th scope="col">State updated</th>@endif
                </tr></thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td><div class="story-cell"><span class="book-tile">@include('projects.quickreads.admin.icon', ['name' => 'stories'])</span><div>
                            <a class="record-title" href="{{ $reportUrl(['story_id' => $row->story_id, 'view' => 'readers']) }}">{{ $row->title ?? 'Unavailable story' }}</a>
                            <small>{{ $row->author ?? 'Unknown author' }} · #{{ $row->story_id }}</small>
                        </div></div></td>
                        <td>
                            @if($view === 'stories')
                                <span class="badge">{{ $row->reader_kind === 'guest' ? 'Guests' : 'Accounts' }}</span>
                                <small class="activity-detail">{{ number_format($row->reports) }} {{ Str::plural('report', $row->reports) }}</small>
                            @elseif($row->reader_kind === 'account')
                                @php($readerName = trim(($row->first_name ?? '').' '.($row->last_name ?? '')) ?: 'Reader #'.$row->account_id)
                                @if($row->account_id)<a class="record-title" href="/quickreads/users/{{ $row->account_id }}">{{ $readerName }}</a>@else<span>Unavailable account</span>@endif
                                <small class="activity-detail">Account #{{ $row->account_id ?? '—' }} @if($row->is_apple) · Apple @endif</small>
                            @else
                                <span class="record-title">Guest reader</span><small class="activity-detail">Guest installation</small>
                            @endif
                        </td>
                        @if($view === 'readers')
                            <td>@include('projects.quickreads.admin.story-state', ['field' => 'reading'])</td>
                            <td>@include('projects.quickreads.admin.story-state', ['field' => 'saved'])</td>
                            <td class="nowrap">@include('projects.quickreads.statistics.activity-time', ['value' => $row->updated_at])</td>
                        @else
                            <td>{{ number_format($row->saved) }}</td><td>{{ number_format($row->reading) }}</td><td>{{ number_format($row->finished) }}</td>
                            <td class="nowrap">@include('projects.quickreads.statistics.activity-time', ['value' => $row->last_received_at])</td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $view === 'readers' ? 5 : 6 }}" class="empty-state">
                        <h3>{{ array_filter($filterLabels) ? 'No matching activity' : 'Waiting for the first report' }}</h3>
                        <p>In the iOS app, save a story, start reading, or mark it finished. Then refresh this report.</p>
                        <p>Reports appear once your app build has activity reporting enabled.</p>
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @include('projects.quickreads.admin.pagination', ['records' => $rows])
    </section>
    <p class="activity-report-note">Each report keeps the latest known state, so Finished replaces Reading for the same reader and story. Counts include reports active within {{ config('quickreads_v2.activity_retention_days') }} days; guest counts represent installations. State updated is when the backend applied a report. Duplicate or older reports do not move that time forward. Select Refresh report to check again.</p>
@endsection
