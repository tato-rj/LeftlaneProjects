@extends('projects.quickreads.layouts.app')
@section('title', 'Users')
@section('content')
    <a class="back-link" href="/quickreads/users">
        ← All readers
    </a>
    @include('projects.quickreads.admin.heading', ['eyebrow' => 'READER #'.$user->id, 'heading' => trim($user->first_name.' '.$user->last_name) ?: 'Reader', 'description' => $user->email])
    <div class="detail-grid">
        <aside>
            <section class="panel profile-panel">
                <span class="avatar large">
                    {{ mb_substr($user->first_name, 0, 1) }}
                </span>
                <h2>
                    Reader details
                </h2>
                <dl>
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
            <section class="panel">
                <div class="panel-heading">
                    <div>
                        <h2>
                            Saved stories
                        </h2>
                        <p>
                            Most recent first · Each row is a saved-story record
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
