<div class="page-heading">
    <div>
        <p class="eyebrow">
            {{ $eyebrow ?? 'QUICKREADS WORKSPACE' }}
        </p>
        <h1>
            {{ $heading }}
        </h1>
        <p class="page-description">
            {{ $description }}
        </p>
    </div>
    @isset($action)
        <a class="button primary" href="{{ $action[1] }}">
            @include('projects.quickreads.admin.icon', ['name' => $action[2] ?? 'plus'])
            {{ $action[0] }}
        </a>
    @endisset
</div>
