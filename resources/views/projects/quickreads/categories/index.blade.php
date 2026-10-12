@extends('projects.quickreads.layouts.app')
@section('title', 'Categories')
@section('content')
    @include('projects.quickreads.admin.heading', ['eyebrow' => 'THE LIBRARY', 'heading' => 'Categories', 'description' => 'Give every story a place. Organize the categories readers explore.', 'action' => ['Add a category', '/quickreads/categories/add']])
    <form method="GET" class="filter-bar standalone">
        @include('projects.quickreads.admin.search', ['placeholder' => 'Search categories…'])
        @include('projects.quickreads.admin.page-size')
        <button class="button primary" type="submit">
            Search
        </button>
        <a class="text-link" href="/quickreads/categories">
            Clear
        </a>
    </form>
    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>
                    {{ number_format($categories->total()) }} categories
                </h2>
                <p>
                    Displayed in reading-app order; lower numbers appear first
                </p>
            </div>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>
                            Category
                        </th>
                        <th>
                            Display order
                        </th>
                        <th>
                            Stories
                        </th>
                        <th>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td>
                                <a class="record-title" href="/quickreads/categories/edit/{{ $category->slug }}">
                                    {{ $category->category }}
                                </a>
                            </td>
                            <td>
                                {{ $category->sorting_order ?? "Unordered" }}
                            </td>
                            <td>
                                <a class="count-link" href="/quickreads/stories?category_id={{ $category->id }}">
                                    {{ number_format($category->stories_count) }} stories →
                                </a>
                            </td>
                            <td>
                                <a class="row-action" href="/quickreads/categories/edit/{{ $category->slug }}">
                                    Edit ↗
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty-state">
                                <h3>
                                    No categories found
                                </h3>
                                <p>
                                    Try a different search, or add your first category.
                                </p>
                                <a class="button" href="/quickreads/categories/add">
                                    Add category
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('projects.quickreads.admin.pagination', ['records' => $categories])
    </section>
@endsection
