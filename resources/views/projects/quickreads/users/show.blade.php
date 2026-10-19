@extends('projects.quickreads.layouts.app')
@section('title', 'Reader profile')
@section('content')
    <a class="back-link" href="/quickreads/users">
        ← All readers
    </a>
    @include('projects.quickreads.admin.heading', ['eyebrow' => 'READER #'.$user->id, 'heading' => $user->display_name, 'description' => $user->email, 'action' => ['Refresh profile', '/quickreads/users/'.$user->id, 'refresh']])
    <div class="detail-grid">
        <aside>
            <section class="panel profile-panel">
                <span class="avatar large">
                    {{ mb_substr($user->display_name, 0, 1) }}
                </span>
                <h2>
                    Reader details
                </h2>
                @include('projects.quickreads.admin.provider', ['apple' => $user->is_apple])
                <dl>
                    @if($user->is_apple)
                        <dt>Apple authorization</dt><dd>{{ $apple->active ? 'Authorized' : 'Revoked' }}</dd>
                        <dt>Last Apple authorization</dt><dd>@include('projects.quickreads.statistics.activity-time', ['value' => $apple->authorized_at])</dd>
                        @if(Str::endsWith(Str::lower($user->email), '@privaterelay.appleid.com'))<dt>Email privacy</dt><dd>Apple private relay address</dd>@endif
                    @endif
                    <dt>
                        Email
                    </dt>
                    <dd>
                        {{ $user->email }}
                    </dd>
                    <dt>
                        Joined
                    </dt>
                    <dd>
                        {{ $user->created_at ? \Carbon\Carbon::parse($user->created_at)->format('F j, Y') : 'Unknown' }}
                    </dd>
                    <dt>
                        Locale
                    </dt>
                    <dd>
                        {{ $user->locale ?: 'Not provided' }}
                    </dd>
                    <dt>
                        Gender
                    </dt>
                    <dd>
                        {{ $user->gender ?: 'Not provided' }}
                    </dd>
                </dl>
            </section>
            @include('projects.quickreads.admin.delete', ['kind' => 'user', 'deleteUrl' => '/quickreads/users/id/'.$user->id, 'warning' => 'This removes the account, saved stories, ratings, comments and access tokens.'])
        </aside>
        <div>
            <div class="metric-grid compact">
                @foreach($counts as $label => $count)
                    <div class="metric-card">
                        <span>
                            {{ ucfirst($label) }}
                        </span>
                        <strong>
                            {{ number_format($count) }}
                        </strong>
                    </div>
                @endforeach
            </div>
            <section class="panel saved-stories-panel" id="saved-stories">
                <div class="panel-heading">
                    <div>
                        <h2>
                            Saved stories
                        </h2>
                        <p>
                            Stories saved through any app version · Most recent first
                        </p>
                    </div>
                </div>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>
                                    Story
                                </th>
                                <th>
                                    Saved on
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($saves as $save)
                                <tr>
                                    <td>
                                        @if($save->slug)
                                            <a class="record-title" href="/quickreads/stories/edit/{{ $save->slug }}">
                                                {{ $save->title }}
                                            </a>
                                        @else
                                            <span class="muted">
                                                Story no longer available
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $save->created_at ? \Carbon\Carbon::parse($save->created_at)->format('M j, Y') : 'Date not recorded' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="empty-state">
                                        No saved stories yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @include('projects.quickreads.admin.pagination', ['records' => $saves])
            </section>
            <section class="panel reader-activity-panel">
                <div class="panel-heading"><div><p class="eyebrow">NEW APP REPORTS</p><h2>Their stories, right now.</h2>
                    <p>Reading, completion and saved state reported by the updated app.</p></div>
                    <a class="text-link" href="/quickreads/story-activity?user_id={{ $user->id }}">All story reports →</a></div>
                @if($user->activity->reports)
                <div class="reader-status-grid">
                    @foreach(['reading' => 'Reading', 'finished' => 'Finished', 'saved' => 'App-reported saved'] as $key => $label)
                        <a href="/quickreads/story-activity?user_id={{ $user->id }}&amp;status={{ $key }}" class="reader-status {{ $key }}"><strong>{{ number_format($user->activity->$key) }}</strong><span>{{ $label }}</span></a>
                    @endforeach
                </div>
                @endif
                <div class="table-scroll"><table class="reader-stories-table">
                    <thead><tr><th scope="col">Story</th><th scope="col">Reading state</th><th scope="col">Saved state</th></tr></thead>
                    <tbody>
                    @forelse($currentStories as $row)
                        <tr><td><a class="record-title" href="/quickreads/story-activity?user_id={{ $user->id }}&amp;story_id={{ $row->story_id }}">{{ $row->title ?? 'Unavailable story' }}</a><small class="activity-detail">{{ $row->author ?? 'Unknown author' }} · #{{ $row->story_id }}</small><small class="activity-detail">Updated @include('projects.quickreads.statistics.activity-time', ['value' => $row->updated_at])</small></td>
                            <td>@include('projects.quickreads.admin.story-state', ['field' => 'reading'])</td>
                            <td>@include('projects.quickreads.admin.story-state', ['field' => 'saved'])</td></tr>
                    @empty
                        <tr><td colspan="3" class="empty-state"><h3>No current app reports</h3><p>Reading and Finished appear when this account reports activity from the updated app. Saved stories from all app versions are listed above.</p></td></tr>
                    @endforelse
                    </tbody>
                </table></div>
                <div class="reader-activity-footer"><span>Showing the latest {{ $currentStories->count() }} of {{ number_format($user->activity->reports) }} current reports · {{ config('quickreads_v2.activity_retention_days') }}-day retention</span>
                    <span>Latest update: @include('projects.quickreads.statistics.activity-time', ['value' => $user->activity->last_received_at, 'empty' => 'No reports yet'])</span></div>
            </section>
            <div class="two-column spaced">
                <section class="panel">
                    <div class="panel-heading">
                        <div>
                            <h2>
                                Latest ratings
                            </h2>
                            <p>
                                Up to 10 recent ratings
                            </p>
                        </div>
                    </div>
                    <div class="activity-list">
                        @forelse($ratings as $rating)
                            <div>
                                <strong>
                                    {{ $rating->title ?? 'Deleted story' }}
                                </strong>
                                <p>
                                    {{ $rating->score }} / 5 stars
                                </p>
                            </div>
                        @empty
                            <p class="empty-state">
                                No ratings yet.
                            </p>
                        @endforelse
                    </div>
                </section>
                <section class="panel">
                    <div class="panel-heading">
                        <div>
                            <h2>
                                Latest comments
                            </h2>
                            <p>
                                Up to 10 recent comments
                            </p>
                        </div>
                    </div>
                    <div class="activity-list">
                        @forelse($comments as $comment)
                            <div>
                                <strong>
                                    {{ $comment->title ?? 'Deleted story' }}
                                </strong>
                                <p>
                                    {{ $comment->body }}
                                </p>
                            </div>
                        @empty
                            <p class="empty-state">
                                No comments yet.
                            </p>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
