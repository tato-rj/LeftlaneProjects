@extends('projects.quickreads.layouts.app')
@section('title', 'Users')
@section('content')
    @include('projects.quickreads.admin.heading', ['eyebrow' => 'YOUR COMMUNITY', 'heading' => 'Readers', 'description' => 'Find a reader, understand their activity and manage their account.'])
    <form method="GET" class="filter-bar standalone">
        @include('projects.quickreads.admin.search', ['placeholder' => 'Search name or email…'])
        <select name="activity" aria-label="Filter by saved stories">
            <option value="">
                All readers
            </option>
            <option value="saved" @selected(request('activity') === 'saved')>
                Have saved stories
            </option>
            <option value="none" @selected(request('activity') === 'none')>
                No saved stories
            </option>
        </select>
        <select name="sort" aria-label="Sort users">
            @foreach(['newest' => 'Newest first', 'oldest' => 'Oldest first', 'name' => 'Name A–Z'] as $value => $text)
                <option value="{{ $value }}" @selected(request('sort', 'newest') === $value)>
                    {{ $text }}
                </option>
            @endforeach
        </select>
        @include('projects.quickreads.admin.page-size')
        <button class="button primary" type="submit">
            Search
        </button>
        <a class="text-link" href="/quickreads/users">
            Clear
        </a>
    </form>
    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>
                    {{ number_format($users->total()) }} readers
                </h2>
                <p>
                    Select a reader to see their saved stories and recent activity.
                </p>
            </div>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>
                            Reader
                        </th>
                        <th>
                            Email
                        </th>
                        <th>
                            Locale
                        </th>
                        <th>
                            Story saves
                        </th>
                        <th>
                            Joined
                        </th>
                        <th>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>
                                <a class="reader-cell record-title" href="/quickreads/users/{{ $user->id }}">
                                    <span class="avatar">
                                        {{ mb_substr($user->first_name, 0, 1) }}
                                    </span>
                                    <span>
                                        {{ $user->first_name }} {{ $user->last_name }}
                                        <small class="muted">
                                            #{{ $user->id }}
                                        </small>
                                    </span>
                                </a>
                            </td>
                            <td>
                                {{ $user->email }}
                            </td>
                            <td>
                                {{ $user->locale ?: '—' }}
                            </td>
                            <td>
                                {{ number_format($user->saves) }}
                            </td>
                            <td class="nowrap">
                                {{ $user->created_at ? \Carbon\Carbon::parse($user->created_at)->format('M j, Y') : '—' }}
                            </td>
                            <td>
                                <a class="row-action" href="/quickreads/users/{{ $user->id }}">
                                    View ↗
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-state">
                                <h3>
                                    No readers found
                                </h3>
                                <p>
                                    Try another name or email, or clear your filters.
                                </p>
                                <a href="/quickreads/users" class="button">
                                    Clear filters
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('projects.quickreads.admin.pagination', ['records' => $users])
    </section>
@endsection
