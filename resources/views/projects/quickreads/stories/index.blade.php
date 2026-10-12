@extends('projects.quickreads.layouts.app')
@section('title', 'Stories')
@section('content')
    @include('projects.quickreads.admin.heading', ['eyebrow' => 'THE LIBRARY', 'heading' => 'Stories', 'description' => 'Find, refine and grow your collection. Every good read starts here.', 'action' => ['Add a story', '/quickreads/stories/add']])
    <form method="GET" class="filter-panel">
        <div class="filter-bar">
            @include('projects.quickreads.admin.search', ['placeholder' => 'Search stories or authors…'])
            <select name="sort" aria-label="Sort stories">
                @foreach(['newest' => 'Newest first', 'oldest' => 'Oldest first', 'name' => 'Title A–Z'] as $value => $text)
                    <option value="{{ $value }}" @selected(request('sort', 'newest') === $value)>
                        {{ $text }}
                    </option>
                @endforeach
            </select>
            @include('projects.quickreads.admin.page-size')
        </div>
        <div class="filter-grid">
            @include('projects.quickreads.admin.lookup', ['type' => 'authors', 'field' => 'author_id', 'label' => 'Author', 'selected' => $selectedAuthor])
            @include('projects.quickreads.admin.lookup', ['type' => 'categories', 'field' => 'category_id', 'label' => 'Category', 'selected' => $selectedCategory])
            <div>
                <label for="classic">
                    Collection
                </label>
                <select id="classic" name="classic">
                    <option value="">
                        All stories
                    </option>
                    <option value="1" @selected(request('classic') === '1')>
                        Classics
                    </option>
                    <option value="0" @selected(request('classic') === '0')>
                        Other stories
                    </option>
                </select>
            </div>
            <div>
                <label for="issue">
                    Library checks
                </label>
                <select id="issue" name="issue">
                    <option value="">
                        All records
                    </option>
                    <option value="relationships" @selected(request('issue') === 'relationships')>
                        Missing author / category
                    </option>
                    <option value="time" @selected(request('issue') === 'time')>
                        Invalid reading time
                    </option>
                </select>
            </div>
        </div>
        <div class="filter-actions">
            <button class="button primary" type="submit">
                Apply filters
            </button>
            <a class="text-link" href="/quickreads/stories">
                Clear all
            </a>
            <span class="muted">
                {{ number_format($stories->total()) }} {{ Str::plural('story', $stories->total()) }}
            </span>
        </div>
    </form>
    <section class="panel">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>
                            Story / Author
                        </th>
                        <th>
                            Category
                        </th>
                        <th>
                            Reading time
                        </th>
                        <th>
                            Views
                        </th>
                        <th>
                            Added
                        </th>
                        <th>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stories as $story)
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
                                            @if($story->is_classic)
                                                <span class="classic-label">
                                                    · Classic
                                                </span>
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge {{ !$story->category ? 'warning' : '' }}">
                                    {{ $story->category ?? 'Missing category' }}
                                </span>
                            </td>
                            <td>
                                {{ is_numeric($story->time) ? max(1, (int) round((float) $story->time)) : $story->time }} min
                            </td>
                            <td>
                                {{ number_format($story->views) }}
                            </td>
                            <td class="nowrap">
                                {{ $story->created_at ? \Carbon\Carbon::parse($story->created_at)->format('M j, Y') : '—' }}
                            </td>
                            <td>
                                <a class="row-action" href="/quickreads/stories/edit/{{ $story->slug }}">
                                    Edit ↗
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-state">
                                <h3>
                                    No stories found
                                </h3>
                                <p>
                                    Try another search or clear your filters.
                                </p>
                                <a class="button" href="/quickreads/stories">
                                    Clear filters
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('projects.quickreads.admin.pagination', ['records' => $stories])
    </section>
@endsection
