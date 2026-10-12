@extends('projects.quickreads.layouts.app')
@section('title', 'Authors')
@section('content')
    <a class="back-link" href="/quickreads/authors">
        ← All authors
    </a>
    @include('projects.quickreads.admin.heading', ['eyebrow' => 'AUTHOR PROFILE', 'heading' => $author ? 'Edit author' : 'Add an author', 'description' => 'Introduce the person behind the pages.'])
    <div class="editor-grid">
        <form method="POST" action="{{ $author ? '/quickreads/authors/'.$author->slug : '/quickreads/authors' }}" data-editor>
            @csrf
            @if($author)
                @method('PATCH')
            @endif
            <section class="panel form-panel">
                <h2>
                    Author details
                </h2>
                <div class="field">
                    <label for="name">
                        Full name
                        <span>
                            *
                        </span>
                    </label>
                    <input id="name" name="name" maxlength="140" value="{{ old('name', $author->name ?? '') }}" required>
                    @error('name')
                        <small class="field-error">
                            {{ $message }}
                        </small>
                    @enderror
                </div>
                <div class="two-column">
                    <div class="field">
                        <label for="born_in">
                            Born
                        </label>
                        <input id="born_in" name="born_in" maxlength="30" value="{{ old('born_in', $author->born_in ?? '') }}" placeholder="e.g. 1882">
                    </div>
                    <div class="field">
                        <label for="died_in">
                            Died
                        </label>
                        <input id="died_in" name="died_in" maxlength="30" value="{{ old('died_in', $author->died_in ?? '') }}" placeholder="Leave blank if living">
                    </div>
                </div>
                <div class="field">
                    <label for="life">
                        Biography
                        <span>
                            *
                        </span>
                    </label>
                    <textarea id="life" name="life" rows="12" maxlength="100000" required>{{ old('life', $author->life ?? '') }}</textarea>
                    <small>
                        This biography appears alongside their stories in the app.
                    </small>
                    @error('life')
                        <small class="field-error">
                            {{ $message }}
                        </small>
                    @enderror
                </div>
                <div class="form-actions">
                    <a class="button" href="/quickreads/authors">
                        Cancel
                    </a>
                    <button class="button primary" type="submit">
                        {{ $author ? 'Save changes' : 'Add author' }}
                    </button>
                </div>
            </section>
        </form>
        <aside>
            <section class="editor-tip">
                <p class="eyebrow">
                    BEHIND THE WORDS
                </p>
                <h2>
                    A little context goes a long way.
                </h2>
                <p>
                    Help readers connect with the author. A few thoughtful paragraphs about their life and work are a good place to start.
                </p>
                @if($author)
                    <a class="button" href="/quickreads/stories?author_id={{ $author->id }}">
                        View {{ $author->stories_count }} stories →
                    </a>
                @endif
            </section>
            @if($author)
                @include('projects.quickreads.admin.delete', ['kind' => 'author', 'deleteUrl' => '/quickreads/authors/'.$author->slug, 'warning' => 'Authors can only be deleted after all their stories have been reassigned.'])
            @endif
        </aside>
    </div>
@endsection
