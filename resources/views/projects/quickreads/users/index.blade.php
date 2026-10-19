@extends('projects.quickreads.layouts.app')
@section('title', 'Readers')
@section('content')
    @include('projects.quickreads.admin.heading', ['eyebrow' => 'YOUR COMMUNITY', 'heading' => 'The people behind the stories.', 'description' => 'Find Apple signups, follow reading activity and open each reader’s story states.'])
    <div class="community-overview">
        <a href="/quickreads/users"><strong>{{ number_format($readerSummary['users']) }}</strong> registered readers</a>
        <a href="/quickreads/users?provider=apple"><strong>{{ number_format($readerSummary['apple_users']) }}</strong> Apple readers</a>
        <span><strong>{{ number_format($readerSummary['new_users']) }}</strong> joined in the last 30 days</span>
        <span class="freshness">Updates on refresh</span>
    </div>
    <form method="GET" class="filter-bar standalone reader-filters">
        @include('projects.quickreads.admin.search', ['placeholder' => 'Search name or email…'])
        <select name="provider" aria-label="Sign-in method">
            <option value="">All sign-in methods</option>
            <option value="apple" @selected(($criteria['provider'] ?? '') === 'apple')>Apple</option>
            <option value="other" @selected(($criteria['provider'] ?? '') === 'other')>Other sign-in</option>
        </select>
        <select name="status" aria-label="Reported story state">
            <option value="">All story states</option>
            @foreach(['reading' => 'Reading', 'finished' => 'Finished', 'saved' => 'Saved', 'not_started' => 'Not started', 'unreported' => 'No current reports'] as $key => $label)
                <option value="{{ $key }}" @selected(($criteria['status'] ?? '') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        @if(!empty($criteria['activity']))<input type="hidden" name="activity" value="{{ $criteria['activity'] }}">@endif
        <select name="sort" aria-label="Sort readers">
            @foreach(['newest' => 'Newest first', 'oldest' => 'Oldest first', 'name' => 'Name A–Z'] as $value => $label)
                <option value="{{ $value }}" @selected(($criteria['sort'] ?? 'newest') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @include('projects.quickreads.admin.page-size')
        <button class="button primary" type="submit">Apply filters</button>
        <a class="text-link" href="/quickreads/users">Clear filters</a>
    </form>
    <section class="panel">
        <div class="panel-heading"><div><h2>{{ number_format($users->total()) }} {{ Str::plural('reader', $users->total()) }}</h2>
            <p>Reading and Finished are current app reports. Saved is tracked separately.</p></div></div>
        <div class="table-scroll">
            <table class="readers-table">
                <thead><tr><th scope="col">Reader</th><th scope="col">Sign-in</th><th scope="col">Reading activity</th><th scope="col">Saved stories</th><th scope="col">Latest state update</th></tr></thead>
                <tbody>
                @forelse($users as $user)
                    <tr>
                        <td><a class="reader-cell record-title" href="/quickreads/users/{{ $user->id }}"><span class="avatar">{{ mb_substr($user->display_name, 0, 1) }}</span><span>{{ $user->display_name }}<small class="muted">#{{ $user->id }}</small></span></a>
                            <small class="reader-email">{{ $user->email }}</small><small class="activity-detail">Joined {{ $user->created_at ? \Carbon\Carbon::parse($user->created_at)->format('M j, Y') : '—' }}</small></td>
                        <td>@include('projects.quickreads.admin.provider', ['apple' => $user->is_apple])</td>
                        <td><div class="state-links">
                            <a class="badge activity-state reading" href="/quickreads/story-activity?user_id={{ $user->id }}&amp;status=reading">{{ number_format($user->activity->reading) }} Reading</a>
                            <a class="badge activity-state finished" href="/quickreads/story-activity?user_id={{ $user->id }}&amp;status=finished">{{ number_format($user->activity->finished) }} Finished</a>
                        </div><small class="activity-detail">{{ number_format($user->activity->reports) }} current {{ Str::plural('story', $user->activity->reports) }}</small></td>
                        <td><a class="record-title" href="/quickreads/story-activity?user_id={{ $user->id }}&amp;status=saved">{{ number_format($user->activity->saved) }} reported saved</a>
                            <small class="activity-detail">{{ number_format($user->saves) }} legacy save records</small></td>
                        <td>@include('projects.quickreads.statistics.activity-time', ['value' => $user->activity->last_received_at, 'empty' => 'No reports yet'])</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty-state"><h3>No readers found</h3><p>Try another search or clear your filters.</p><a href="/quickreads/users" class="button">Clear filters</a></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @include('projects.quickreads.admin.pagination', ['records' => $users])
    </section>
    <p class="data-note">Story states reflect reports updated within {{ config('quickreads_v2.activity_retention_days') }} days. Guest reports are available in <a href="/quickreads/story-activity?reader_kind=guest">Story activity</a>. Apple readers remain listed if their Apple authorization is revoked; open their profile to check its status.</p>
@endsection
