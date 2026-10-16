<aside class="sidebar" id="sidebar">
    <a href="/quickreads" class="brand">
        <span class="brand-symbol">
            @include('projects.quickreads.admin.icon', ['name' => 'stories'])
        </span>
        <span>
            Quickreads
            <small>
                ADMIN WORKSPACE
            </small>
        </span>
    </a>
    <div class="nav-label">
        OVERVIEW
    </div>
    <nav aria-label="Main navigation">
        @foreach(['dashboard' => ['Dashboard', '/quickreads'], 'statistics' => ['Statistics', '/quickreads/statistics'], 'story-activity' => ['Story activity', '/quickreads/story-activity'], 'users' => ['Users', '/quickreads/users'], 'explore' => ['Explore', '/quickreads/explore'], 'stories' => ['Stories', '/quickreads/stories'], 'authors' => ['Authors', '/quickreads/authors'], 'categories' => ['Categories', '/quickreads/categories']] as $icon => $item)
            @if($icon === 'stories')
                <div class="nav-label nav-divider">
                    LIBRARY
                </div>
            @endif
            @php($active = $icon === 'dashboard' ? request()->is('quickreads') : request()->is('quickreads/'.$icon, 'quickreads/'.$icon.'/*'))
            <a href="{{ $item[1] }}" class="nav-link {{ $active ? 'active' : '' }}" @if($active) aria-current="page" @endif>
                @include('projects.quickreads.admin.icon', ['name' => $icon])
                <span>
                    {{ $item[0] }}
                </span>
                @if($active)
                    <span class="nav-dot">
                    </span>
                @endif
            </a>
        @endforeach
    </nav>
    <div class="sidebar-bottom">
        <div class="sidebar-note">
            Small stories.
            <br>
            <strong>
                Big possibilities.
            </strong>
        </div>
        <form method="POST" action="{{ route('logoutFrom', 'quickreads-admin') }}">
            @csrf
            <button class="nav-link" type="submit">
                @include('projects.quickreads.admin.icon', ['name' => 'logout'])
                Sign out
            </button>
        </form>
    </div>
</aside>
<button type="button" class="nav-backdrop" aria-label="Close navigation" hidden>
</button>
