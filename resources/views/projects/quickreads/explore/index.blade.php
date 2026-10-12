@extends('projects.quickreads.layouts.app')
@section('title', 'Explore')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/quickreads-explore.css') }}?v={{ filemtime(public_path('css/quickreads-explore.css')) }}">
@endpush
@push('scripts')
    <script src="{{ asset('js/quickreads-explore.js') }}?v={{ filemtime(public_path('js/quickreads-explore.js')) }}" defer></script>
@endpush
@section('content')
    <div class="explore-heading">
        <div>
            <p class="eyebrow">THE READER’S FRONT PAGE</p>
            <h1>Make room for discovery.</h1>
            <p class="muted">Your sections, your stories. Drag to arrange and choose exactly what readers see.</p>
        </div>
        <div class="explore-save">
            <span data-save-state>All changes saved</span>
            <button type="button" class="button primary" data-save-explore disabled>Save Explore</button>
        </div>
    </div>
    <div class="explore-message" data-explore-message role="status" hidden></div>
    <div class="explore-toolbar"><span>Compact preview · same stories and order as the app</span><button type="button" class="button" data-add-section>＋ Add a section</button></div>
    <noscript><div class="notice error">Enable JavaScript to arrange and preview Explore.</div></noscript>
    <div class="explore-workspace" data-explore-editor>
        <div class="explore-canvas-wrap">
            <div class="explore-canvas-label"><span>APP PREVIEW</span><span>Content &amp; order · your changes stay private until saved</span></div>
            <div class="explore-canvas">
                <div class="explore-app-heading"><span class="explore-app-mark">q.</span><div><small>A LITTLE READING, EVERY DAY</small><h2>Explore</h2></div></div>
                <div data-explore-sections></div>
                <button type="button" class="explore-add" data-add-section>＋ Add a section</button>
                <p class="explore-preview-note">The app controls the final typography and spacing. Readers can expand story rows with See all. Continue reading is added by their app.</p>
            </div>
        </div>
        <aside class="explore-inspector" aria-label="Section editor">
            <div class="explore-inspector-header"><p class="eyebrow">SECTION DETAILS</p><span class="badge" data-section-kind></span></div>
            <h2 data-inspector-heading>Today’s pick</h2>
            <p class="muted explore-inspector-intro" data-section-help></p>
            <div class="field" data-title-field>
                <label for="section-title">Heading</label>
                <input id="section-title" maxlength="80" autocomplete="off">
            </div>
            <p class="explore-selection-note" data-selection-note></p>
            <div data-section-picker></div>
            <details class="explore-automatic">
                <summary>Automatic selection options</summary>
            <div class="field">
                <label for="section-source">Selection method</label>
                <select id="section-source"></select>
                <small data-source-help></small>
            </div>
            <div class="field" data-limit-field>
                <label for="section-limit">Stories in this section</label>
                <select id="section-limit">
                    @foreach(range(1, 24) as $limit)<option value="{{ $limit }}">{{ $limit }} {{ $limit === 1 ? 'story' : 'stories' }}</option>@endforeach
                </select>
            </div>
            <div class="field" data-minutes-field hidden>
                <label for="section-minutes">Maximum reading time (minutes)</label>
                <input type="number" id="section-minutes" min="0.1" max="1440" step="0.1">
            </div>
            <div class="field" data-filter-field hidden>
                <label for="section-filter" data-filter-label></label>
                <input type="search" id="section-filter" autocomplete="off" placeholder="Search…" aria-controls="explore-filter-results" aria-expanded="false">
                <div id="explore-filter-results" class="explore-filter-results" hidden></div>
                <small data-filter-selected></small>
            </div>
            </details>
            <div class="explore-section-actions" data-section-actions>
                <div class="explore-move-actions">
                    <button type="button" class="button" data-move-section="-1" aria-label="Move selected section up">↑ Move up</button>
                    <button type="button" class="button" data-move-section="1" aria-label="Move selected section down">↓ Move down</button>
                </div>
                <button type="button" class="explore-remove" data-remove-section>Remove section</button>
            </div>
        </aside>
    </div>
    <dialog class="explore-dialog" data-section-dialog aria-labelledby="new-section-heading">
        <form data-new-section-form>
            <div class="explore-dialog-heading"><div><p class="eyebrow">CURATE A NEW SHELF</p><h2 id="new-section-heading">Add a section</h2></div><button type="button" class="icon-button" data-close-dialog aria-label="Close new section form">×</button></div>
            <p class="muted">Give it a name, then choose stories from your library.</p>
            <div class="field"><label for="new-section-title">Section name</label><input id="new-section-title" placeholder="e.g. Stories for a quiet evening" maxlength="80" required autocomplete="off"></div>
            <div data-new-section-picker></div>
            <div class="explore-dialog-actions"><small>Private until you save Explore.</small><button type="button" class="button" data-close-dialog>Cancel</button><button type="submit" class="button primary" data-create-section disabled>Create section</button></div>
        </form>
    </dialog>
    <p class="sr-only" id="explore-drag-help">Drag to rearrange. You can also use the arrow keys on a drag handle. Today’s pick stays first.</p>
    <div class="sr-only" aria-live="polite" data-drag-announcement></div>
    <script type="application/json" id="explore-initial">@json($initial)</script>
@endsection
