@extends('projects.quickreads.layouts.app')
@section('title', 'Categories')
@section('content')
    <a class="back-link" href="/quickreads/categories">
        ← All categories
    </a>
    @include('projects.quickreads.admin.heading', ['eyebrow' => 'LIBRARY ORGANIZATION', 'heading' => $category ? 'Edit category' : 'Add a category', 'description' => 'Help readers find the kind of story they’re in the mood for.'])
    <div class="editor-grid">
        <form method="POST" action="{{ $category ? '/quickreads/categories/'.$category->slug : '/quickreads/categories' }}" data-editor>
            @csrf
            @if($category)
                @method('PATCH')
            @endif
            <section class="panel form-panel">
                <h2>
                    Category details
                </h2>
                <div class="field">
                    <label for="category">
                        Category name
                        <span>
                            *
                        </span>
                    </label>
                    <input id="category" name="category" maxlength="120" value="{{ old('category', $category->category ?? '') }}" required placeholder="e.g. Mystery">
                    @error('category')
                        <small class="field-error">
                            {{ $message }}
                        </small>
                    @enderror
                </div>
                <div class="field">
                    <label for="sorting_order">
                        Display order
                    </label>
                    <input type="number" id="sorting_order" name="sorting_order" min="-128" max="127" step="1" value="{{ old('sorting_order', $category->sorting_order ?? '') }}" placeholder="e.g. 1">
                    <small>
                        Lower numbers appear first. Leave blank to place this category after numbered categories. Categories with the same number are sorted by name here.
                    </small>
                    @error('sorting_order')
                        <small class="field-error">
                            {{ $message }}
                        </small>
                    @enderror
                </div>
                <div class="form-actions">
                    <a class="button" href="/quickreads/categories">
                        Cancel
                    </a>
                    <button class="button primary" type="submit">
                        {{ $category ? 'Save changes' : 'Add category' }}
                    </button>
                </div>
            </section>
        </form>
        <aside>
            <section class="editor-tip">
                <p class="eyebrow">
                    MAKE ROOM FOR DISCOVERY
                </p>
                <h2>
                    A place for every story.
                </h2>
                <p>
                    Use familiar, distinct names that are easy to browse. Every story belongs to one category.
                </p>
                @if($category)
                    <a class="button" href="/quickreads/stories?category_id={{ $category->id }}">
                        View {{ $category->stories_count }} stories →
                    </a>
                @endif
            </section>
            @if($category)
                @include('projects.quickreads.admin.delete', ['kind' => 'category', 'deleteUrl' => '/quickreads/categories/'.$category->slug, 'warning' => 'Categories can only be deleted after all their stories have been reassigned.'])
            @endif
        </aside>
    </div>
@endsection
