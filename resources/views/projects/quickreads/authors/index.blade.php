@extends('projects.quickreads.layouts.app')
@section('title', 'Authors')
@section('content')
    @include('projects.quickreads.admin.heading', ['eyebrow' => 'THE LIBRARY', 'heading' => 'Authors', 'description' => 'The voices behind your stories. Manage their profiles and biographies.', 'action' => ['Add an author', '/quickreads/authors/add']])
    <form method="GET" class="filter-bar standalone">
        @include('projects.quickreads.admin.search', ['placeholder' => 'Search authors…'])
        @include('projects.quickreads.admin.page-size')
        <button class="button primary" type="submit">
            Search
        </button>
        <a class="text-link" href="/quickreads/authors">
            Clear
        </a>
    </form>
    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>
                    {{ number_format($authors->total()) }} authors
                </h2>
                <p>
                    Sorted alphabetically
                </p>
            </div>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>
                            Author
                        </th>
                        <th>
                            Life dates
                        </th>
                        <th>
                            Stories
                        </th>
                        <th>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($authors as $author)
                        <tr>
                            <td>
                                <a class="record-title" href="/quickreads/authors/edit/{{ $author->slug }}">
                                    {{ $author->name }}
                                </a>
                            </td>
                            <td>
                                {{ $author->born_in ?: "—" }} – {{ $author->died_in ?: "—" }}
                            </td>
                            <td>
                                <a class="count-link" href="/quickreads/stories?author_id={{ $author->id }}">
                                    {{ number_format($author->stories_count) }} stories →
                                </a>
                            </td>
                            <td>
                                <a class="row-action" href="/quickreads/authors/edit/{{ $author->slug }}">
                                    Edit ↗
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty-state">
                                <h3>
                                    No authors found
                                </h3>
                                <p>
                                    Try a different search, or add your first author.
                                </p>
                                <a class="button" href="/quickreads/authors/add">
                                    Add author
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('projects.quickreads.admin.pagination', ['records' => $authors])
    </section>
@endsection
