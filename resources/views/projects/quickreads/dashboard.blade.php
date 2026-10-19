@extends('projects.quickreads.layouts.app')
@section('title', 'Dashboard')
@section('content')
    @include('projects.quickreads.admin.heading', ['eyebrow' => 'QUICKREADS AT A GLANCE', 'heading' => 'Every reader has a story.', 'description' => 'See what’s being read, who’s joining and where your library is growing.', 'action' => ['Add a story', '/quickreads/stories/add']])
    <div class="overview-toolbar"><span class="freshness">Reader activity checked {{ now()->format('M j, g:i:s a T') }}</span><a class="button" href="/quickreads">Refresh overview @include('projects.quickreads.admin.icon', ['name' => 'refresh'])</a></div>
    <div class="metric-grid activity-metrics">
        @foreach([['saved', 'Saved stories', 'bookmark'], ['reading', 'Reading', 'stories'], ['finished', 'Finished stories', 'check'], ['apple', 'Apple readers', 'users']] as [$key, $label, $icon])
            <a class="metric-card overview-metric {{ $key }}" href="{{ $key === 'apple' ? '/quickreads/users?provider=apple' : ($key === 'saved' ? '/quickreads/users?activity=saved' : '/quickreads/story-activity?status='.$key) }}">
                <div class="metric-top"><span>{{ $label }}</span><span class="metric-icon">@include('projects.quickreads.admin.icon', ['name' => $icon])</span></div>
                <strong>{{ number_format($key === 'apple' ? $summary['apple_users'] : ($key === 'saved' ? $savedTotal : $activitySummary->$key)) }}</strong>
                <span class="metric-link">{{ $key === 'apple' ? 'View Apple accounts' : ($key === 'saved' ? 'View saved stories' : 'View story reports') }} @include('projects.quickreads.admin.icon', ['name' => 'arrow'])</span>
            </a>
        @endforeach
    </div>
    <p class="report-caption">Saved stories include saved records from all app versions. Reading and Finished count new app reports updated within {{ config('quickreads_v2.activity_retention_days') }} days.</p>
    <div class="catalog-strip">
        @foreach([['stories', 'Stories', '/quickreads/stories'], ['users', 'Registered readers', '/quickreads/users'], ['authors', 'Authors', '/quickreads/authors'], ['categories', 'Categories', '/quickreads/categories']] as [$key, $label, $url])
            <a href="{{ $url }}"><strong>{{ number_format($summary[$key]) }}</strong><span>{{ $label }}</span><span aria-hidden="true">↗</span></a>
        @endforeach
    </div>
    <section class="panel saved-stories-panel">
        <div class="panel-heading"><div><h2>Latest saved stories</h2><p>Saves from all app versions</p></div><a class="text-link" href="/quickreads/users?activity=saved">Readers with saved stories →</a></div>
        <div class="table-scroll"><table class="recent-activity-table">
            <thead><tr><th scope="col">Story</th><th scope="col">Reader</th><th scope="col">Saved on</th></tr></thead>
            <tbody>
            @forelse($recentSaves as $save)
                <tr>
                    <td>@if($save->slug)<a class="record-title" href="/quickreads/stories/edit/{{ $save->slug }}">{{ $save->title }}</a>@else<span>Unavailable story · #{{ $save->story_id }}</span>@endif</td>
                    <td>@if($save->user_id)<a class="record-title" href="/quickreads/users/{{ $save->user_id }}#saved-stories">{{ trim(($save->first_name ?? '').' '.($save->last_name ?? '')) ?: 'Reader #'.$save->user_id }}</a>@else<span>Unavailable account</span>@endif</td>
                    <td>{{ $save->created_at ? \Carbon\Carbon::parse($save->created_at)->format('M j, Y') : 'Date not recorded' }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="empty-state">No saved stories yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
    <div class="dashboard-grid">
        <section class="panel">
            <div class="panel-heading"><div><p class="eyebrow">FROM THE APP</p><h2>Latest app-reported activity</h2><p>The latest state for each reader and story</p></div><a class="text-link" href="/quickreads/story-activity">All activity →</a></div>
            <div class="table-scroll"><table class="recent-activity-table">
                <thead><tr><th scope="col">Story</th><th scope="col">Reader</th><th scope="col">Current state</th></tr></thead>
                <tbody>
                @forelse($recentActivity as $row)
                    <tr>
                        <td><a class="record-title" href="/quickreads/story-activity?story_id={{ $row->story_id }}">{{ $row->title ?? 'Unavailable story' }}</a><small class="activity-detail">{{ $row->author ?? 'Unknown author' }}</small></td>
                        <td>@if($row->account_id)<a class="record-title" href="/quickreads/users/{{ $row->account_id }}">{{ trim(($row->first_name ?? '').' '.($row->last_name ?? '')) ?: 'Reader #'.$row->account_id }}</a><small class="activity-detail">{{ $row->is_apple ? 'Apple' : 'Account' }} · #{{ $row->account_id }}</small>
                            @else<span>{{ $row->reader_kind === 'guest' ? 'Guest reader' : 'Unavailable account' }}</span>@endif</td>
                        <td><div class="state-links">@include('projects.quickreads.admin.story-state', ['field' => 'reading', 'timestamps' => false])
                            @if($row->saved !== null)@include('projects.quickreads.admin.story-state', ['field' => 'saved', 'timestamps' => false])@endif</div>
                            <small class="activity-detail">@include('projects.quickreads.statistics.activity-time', ['value' => $row->updated_at])</small></td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="empty-state"><h3>Waiting for the first story report</h3><p>Save, read or finish a story in the updated app, then refresh this overview.</p></td></tr>
                @endforelse
                </tbody>
            </table></div>
            <div class="reader-activity-footer">Latest state update: @include('projects.quickreads.statistics.activity-time', ['value' => $activitySummary->last_received_at, 'empty' => 'No reports yet'])</div>
        </section>
        <aside class="activity-panel community-panel">
            <p class="eyebrow">YOUR GROWING COMMUNITY</p><h2>A place for every reader.</h2>
            <div class="activity-stat"><span>Joined in the last 30 days</span><strong>{{ number_format($summary['new_users']) }}</strong></div>
            <div class="activity-stat"><span>New Apple readers</span><strong>{{ number_format($summary['new_apple_users']) }}</strong></div>
            <a class="button" href="/quickreads/users?provider=apple">Explore Apple readers @include('projects.quickreads.admin.icon', ['name' => 'arrow'])</a>
            <p class="activity-note">Apple signups appear here as soon as their account is created, even when Apple doesn’t supply a name.</p>
        </aside>
    </div>
    <div class="dashboard-grid lower-grid">
        <section class="panel">
            <div class="panel-heading"><div><h2>Newest readers</h2><p>All sign-in methods, together</p></div><a class="text-link" href="/quickreads/users">All readers →</a></div>
            <div class="reader-list">
                @forelse($recentUsers as $user)
                    <a href="/quickreads/users/{{ $user->id }}" class="reader-row">
                        <span class="avatar">{{ mb_substr($user->display_name, 0, 1) }}</span>
                        <div><strong>{{ $user->display_name }}</strong><small>{{ $user->email }}</small>@include('projects.quickreads.admin.provider', ['apple' => $user->is_apple])</div>
                        <time>{{ $user->created_at ? \Carbon\Carbon::parse($user->created_at)->format('M j') : '—' }}</time><span aria-hidden="true">↗</span>
                    </a>
                @empty
                    <div class="empty-state">New readers will appear here.</div>
                @endforelse
            </div>
        </section>
        <section class="panel checklist">
            <div class="panel-heading"><div><h2>Library check-in</h2><p>Keep the reading experience in good shape</p></div></div>
            @foreach(['relationships' => ['Authors & categories', 'Stories with a missing author or category'], 'time' => ['Reading times', 'Stories with an invalid reading time']] as $key => [$label, $description])
                <a class="check-row" href="/quickreads/stories?issue={{ $key }}"><span class="check-status {{ $summary[$key] ? 'needs-work' : '' }}">@include('projects.quickreads.admin.icon', ['name' => $summary[$key] ? 'alert' : 'check'])</span><span><strong>{{ $label }}</strong><small>{{ $summary[$key] ? number_format($summary[$key]).' need attention' : 'All looking good' }}</small></span><span aria-hidden="true">→</span></a>
            @endforeach
            <div class="check-tip"><a href="/quickreads/statistics">Explore signup and save statistics →</a></div>
        </section>
    </div>
    <section class="panel latest-library-panel">
        <div class="panel-heading"><div><h2>Latest stories</h2><p>The newest additions to the library</p></div><a class="text-link" href="/quickreads/stories">Manage stories →</a></div>
        <div class="latest-library-grid">
            @forelse($recentStories as $story)
                <a href="/quickreads/stories/edit/{{ $story->slug }}" class="latest-library-story"><span class="book-tile">@include('projects.quickreads.admin.icon', ['name' => 'stories'])</span><span><strong>{{ $story->title }}</strong><small>{{ $story->author ?? 'Missing author' }}</small></span><span aria-hidden="true">↗</span></a>
            @empty
                <p class="empty-state">Your library starts here. <a href="/quickreads/stories/add">Add your first story.</a></p>
            @endforelse
        </div>
    </section>
    <p class="data-note">Reader accounts and story states update on every refresh. Catalogue summary checked {{ $summary['generated_at']->format('M j, g:i a') }}; cached for up to 5 minutes.</p>
@endsection
