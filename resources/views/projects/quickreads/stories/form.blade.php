@extends('projects.quickreads.layouts.app')
@section('title', 'Stories')
@section('content')
    <a class="back-link" href="/quickreads/stories">
        ← All stories
    </a>
    @include('projects.quickreads.admin.heading', ['eyebrow' => 'STORY EDITOR', 'heading' => $story ? 'Edit story' : 'Add a story', 'description' => $story ? 'Give this story the details it deserves.' : 'Bring another good read into the library.'])
    <form method="POST" action="{{ $story ? '/quickreads/stories/'.$story->slug : '/quickreads/stories' }}" enctype="multipart/form-data" data-editor>
        @csrf
        @if($story)
            @method('PATCH')
        @endif
        <div class="editor-grid">
            <div>
                <section class="panel form-panel">
                    <div class="section-title">
                        <span class="section-number">
                            01
                        </span>
                        <div>
                            <h2>
                                The story
                            </h2>
                            <p>
                                The title and description readers see first
                            </p>
                        </div>
                    </div>
                    <div class="field">
                        <label for="title">
                            Title
                            <span>
                                *
                            </span>
                        </label>
                        <input id="title" name="title" maxlength="255" value="{{ old('title', $story->title ?? '') }}" required placeholder="A title worth opening">
                        @error('title')
                            <small class="field-error">
                                {{ $message }}
                            </small>
                        @enderror
                    </div>
                    <div class="field">
                        <label for="summary">
                            Summary
                            <span>
                                *
                            </span>
                        </label>
                        <textarea id="summary" name="summary" rows="4" maxlength="10000" required placeholder="Give readers a reason to turn the first page…">{{ old('summary', $story->summary ?? '') }}</textarea>
                        <small>
                            A short introduction to the story. Keep the ending a surprise.
                        </small>
                        @error('summary')
                            <small class="field-error">
                                {{ $message }}
                            </small>
                        @enderror
                    </div>
                    <div class="field">
                        <div class="label-row">
                            <label for="text">
                                Story text
                                <span>
                                    *
                                </span>
                            </label>
                            <span class="word-count" data-word-count>
                            </span>
                        </div>
                        <textarea class="story-text" id="text" name="text" rows="20" maxlength="2000000" required placeholder="The story begins here…">{{ old('text', $story->text ?? '') }}</textarea>
                        <small>
                            Plain text with paragraph breaks. Formatting is preserved in the reading app.
                        </small>
                        @error('text')
                            <small class="field-error">
                                {{ $message }}
                            </small>
                        @enderror
                    </div>
                </section>
            </div>
            <aside>
                <section class="panel form-panel">
                    <div class="section-title">
                        <span class="section-number">
                            02
                        </span>
                        <div>
                            <h2>
                                Library details
                            </h2>
                            <p>
                                Make it easy to discover
                            </p>
                        </div>
                    </div>
                    <div class="field">
                        @include('projects.quickreads.admin.lookup', ['type' => 'authors', 'field' => 'author_id', 'label' => 'Author *', 'selected' => $selectedAuthor, 'required' => true])
                        @error('author_id')
                            <small class="field-error">
                                {{ $message }}
                            </small>
                        @enderror
                        <a class="small-link" href="/quickreads/authors/add" target="_blank" rel="noopener">
                            Add an author ↗
                        </a>
                    </div>
                    <div class="field">
                        @include('projects.quickreads.admin.lookup', ['type' => 'categories', 'field' => 'category_id', 'label' => 'Category *', 'selected' => $selectedCategory, 'required' => true])
                        @if($automaticCategory)
                            <small>Choose a regular category for this story. Latest additions is managed automatically.</small>
                        @endif
                        @error('category_id')
                            <small class="field-error">
                                {{ $message }}
                            </small>
                        @enderror
                        <a class="small-link" href="/quickreads/categories/add" target="_blank" rel="noopener">
                            Add a category ↗
                        </a>
                    </div>
                    <div class="field">
                        <label for="time">
                            Reading time (minutes)
                            <span>
                                *
                            </span>
                        </label>
                        @php($readingMinutes = old('time', $story->time ?? ''))
                        <input type="number" id="time" name="time" min="1" max="1440" step="1" value="{{ is_numeric($readingMinutes) ? max(1, (int) round((float) $readingMinutes)) : '' }}" required aria-describedby="time-help">
                        <small id="time-help">
                            Automatically estimated at 200 words per minute and rounded to the nearest whole minute as you edit the story text.
                        </small>
                        @error('time')
                            <small class="field-error">
                                {{ $message }}
                            </small>
                        @enderror
                    </div>
                    <input type="hidden" name="is_classic" value="0">
                    <label class="checkbox">
                        <input type="checkbox" name="is_classic" value="1" @checked(old('is_classic', $story->
                        is_classic ?? false))> Part of the classics collection
                    </label>
                </section>
                <section class="panel form-panel spaced">
                    <div class="section-title">
                        <span class="section-number">
                            03
                        </span>
                        <div>
                            <h2>
                                Cover image
                            </h2>
                            <p>
                                Give this read a face
                            </p>
                        </div>
                    </div>
                    <div class="cover-preview" data-cover-preview>
                        @if(!empty($artwork))
                            <img src="{{ $artwork['cover_url'] }}" alt="Current story cover">
                        @else
                            <div class="cover-placeholder">
                                @include('projects.quickreads.admin.icon', ['name' => 'stories'])
                                <span>
                                    A world in a few pages
                                </span>
                            </div>
                        @endif
                    </div>
                    <div class="field">
                        <label for="image">
                            {{ $story ? 'Replace cover' : 'Upload cover' }}
                        </label>
                        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
                        <small>
                            JPEG, PNG or WebP. Maximum 5 MB. Optional.
                        </small>
                        @error('image')
                            <small class="field-error">
                                {{ $message }}
                            </small>
                        @enderror
                    </div>
                </section>
            </aside>
        </div>
        <div class="save-bar">
            <span>
                All fields marked * are required. Saving updates the live catalogue.
            </span>
            <div>
                <a class="button" href="/quickreads/stories">
                    Cancel
                </a>
                <button class="button primary" type="submit">
                    {{ $story ? 'Save changes' : 'Add story' }}
                    @include('projects.quickreads.admin.icon', ['name' => 'check'])
                </button>
            </div>
        </div>
    </form>
    @if($story)
        @include('projects.quickreads.admin.delete', ['kind' => 'story', 'deleteUrl' => '/quickreads/stories/'.$story->slug, 'warning' => 'This removes the story, its cover, saved-story records, ratings and comments. Readers will no longer be able to open it.'])
    @endif
@endsection
