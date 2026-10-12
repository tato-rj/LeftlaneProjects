@extends('projects.quickreads.layouts.app')
@section('title', 'Dashboard')
@section('content')
    @include('projects.quickreads.admin.heading', ['eyebrow' => 'A GOOD DAY FOR A GOOD STORY', 'heading' => 'Your library, at a glance.', 'description' => 'A home for your stories and the people who read them.', 'action' => ['Add a story', '/quickreads/stories/add']])
    <div class="metric-grid">
        @foreach([['stories', 'Stories in the library', 'stories', '/quickreads/stories'], ['users', 'Registered readers', 'users', '/quickreads/users'], ['authors', 'Authors', 'authors', '/quickreads/authors'], ['categories', 'Categories', 'categories', '/quickreads/categories']] as [$key, $label, $icon, $url])
            <a class="metric-card" href="{{ $url }}">
                <div class="metric-top">
                    <span>
                        {{ $label }}
                    </span>
                    <span class="metric-icon">
                        @include('projects.quickreads.admin.icon', ['name' => $icon])
                    </span>
                </div>
                <strong>
                    {{ number_format($summary[$key]) }}
                </strong>
                <span class="metric-link">
                    View {{ $key === 'users' ? 'readers' : $key }}
                    @include('projects.quickreads.admin.icon', ['name' => 'arrow'])
                </span>
            </a>
        @endforeach
    </div>
    <div class="dashboard-grid">
        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>
                        Latest stories
                    </h2>
                    <p>
                        The newest additions to your collection
                    </p>
                </div>
                <a class="text-link" href="/quickreads/stories">
                    All stories →
                </a>
            </div>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>
                                Story
                            </th>
                            <th>
                                Category
                            </th>
                            <th>
                                Read time
                            </th>
                            <th>
                                <span class="sr-only">
                                    Action
                                </span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentStories as $story)
                            <tr>
                                <td>
                                    <div class="story-cell">
                                        <span class="book-tile">
                                            @include('projects.quickreads.admin.icon', ['name' => 'stories'])
                                        </span>
                                        <div>
                                            <a class="record-title" href="/quickreads/stories/edit/{{ $story->slug }}">
                                                {{ $story->title }}
                                            </a>
                                            <small>
                                                {{ $story->author ?? 'Missing author' }}
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge">
                                        {{ $story->category ?? 'Missing category' }}
                                    </span>
                                </td>
                                <td class="nowrap">
                                    {{ is_numeric($story->time) ? max(1, (int) round((float) $story->time)) : $story->time }} min
                                </td>
                                <td>
                                    <a class="row-action" href="/quickreads/stories/edit/{{ $story->slug }}" aria-label="Edit {{ $story->title }}">
                                        Edit ↗
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="empty-state">
                                    Your library starts here.
                                    <a href="/quickreads/stories/add">
                                        Add your first story.
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <aside class="activity-panel">
            <p class="eyebrow">
                THE LAST 30 DAYS
            </p>
            <h2>
                A growing reading habit.
            </h2>
            <div class="activity-stat">
                <span>
                    New readers
                </span>
                <strong>
                    {{ number_format($summary['new_users']) }}
                </strong>
            </div>
            <div class="activity-stat">
                <span>
                    Story saves
                </span>
                <strong>
                    {{ number_format($summary['saves']) }}
                </strong>
            </div>
            <a class="button" href="/quickreads/statistics">
                Explore statistics
                @include('projects.quickreads.admin.icon', ['name' => 'arrow'])
            </a>
            <p class="activity-note">
                Every story saved is another reason to keep reading.
            </p>
        </aside>
    </div>
    <div class="dashboard-grid lower-grid">
        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>
                        Newest readers
                    </h2>
                    <p>
                        Meet the latest people to join Quickreads
                    </p>
                </div>
                <a class="text-link" href="/quickreads/users">
                    All users →
                </a>
            </div>
            <div class="reader-list">
                @forelse($recentUsers as $user)
                    <a href="/quickreads/users/{{ $user->id }}" class="reader-row">
                        <span class="avatar">
                            {{ mb_substr($user->first_name, 0, 1) }}
                        </span>
                        <div>
                            <strong>
                                {{ $user->first_name }} {{ $user->last_name }}
                            </strong>
                            <small>
                                {{ $user->email }}
                            </small>
                        </div>
                        <time>
                            {{ $user->created_at ? \Carbon\Carbon::parse($user->created_at)->format('M j') : '—' }}
                        </time>
                        <span>
                            ↗
                        </span>
                    </a>
                @empty
                    <div class="empty-state">
                        New readers will appear here.
                    </div>
                @endforelse
            </div>
        </section>
        <section class="panel checklist">
            <div class="panel-heading">
                <div>
                    <h2>
                        Library check-in
                    </h2>
                    <p>
                        Keep the reading experience in good shape
                    </p>
                </div>
            </div>
            @foreach(['relationships' => ['Authors & categories', 'Stories with a missing author or category'], 'time' => ['Reading times', 'Stories with an invalid reading time']] as $key => [$label, $description])
                <a class="check-row" href="/quickreads/stories?issue={{ $key }}">
                    <span class="check-status {{ $summary[$key] ? 'needs-work' : '' }}">
                        @include('projects.quickreads.admin.icon', ['name' => $summary[$key] ? 'alert' : 'check'])
                    </span>
                    <span>
                        <strong>
                            {{ $label }}
                        </strong>
                        <small>
                            {{ $summary[$key] ? number_format($summary[$key]).' need attention' : 'All looking good' }}
                        </small>
                    </span>
                    <span>
                        →
                    </span>
                </a>
            @endforeach
            <div class="check-tip">
                Ready to grow the library?
                <br>
                <a href="/quickreads/authors/add">
                    Add an author
                </a>
                or
                <a href="/quickreads/categories/add">
                    create a category
                </a>
                .
            </div>
        </section>
    </div>
    <p class="data-note">
        Summary updated {{ $summary['generated_at']->format('M j, g:i a') }} · Refreshes every 5 minutes.
    </p>
@endsection
