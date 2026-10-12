<div class="search-field">
    <label class="sr-only" for="q">
        {{ $placeholder ?? 'Search' }}
    </label>
    @include('projects.quickreads.admin.icon', ['name' => 'search'])
    <input type="search" id="q" name="q" maxlength="150" value="{{ request('q') }}" placeholder="{{ $placeholder ?? 'Search' }}">
</div>
