<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="noindex, nofollow">
        <title>
            @yield('title', 'Dashboard')
            · Quickreads
        </title>
        <link rel="icon" href="{{ asset('images/icon.svg') }}" type="image/svg+xml">
        <link rel="stylesheet" href="{{ asset('css/quickreads-admin.css') }}?v={{ filemtime(public_path('css/quickreads-admin.css')) }}">
        <script src="{{ asset('js/quickreads-admin.js') }}?v={{ filemtime(public_path('js/quickreads-admin.js')) }}" defer>
        </script>
    </head>
    <body>
        <a class="skip-link" href="#main">
            Skip to content
        </a>
        @include('projects.quickreads.components.nav')
        <div class="workspace">
            <header class="topbar">
                <button class="icon-button menu-toggle" type="button" aria-controls="sidebar" aria-expanded="false" aria-label="Open navigation">
                    @include('projects.quickreads.admin.icon', ['name' => 'menu'])
                </button>
                <div class="topbar-location">
                    Workspace
                    <span>
                        /
                    </span>
                    <strong>
                        @yield('title', 'Dashboard')
                    </strong>
                </div>
                <div class="admin-identity">
                    <span class="avatar">
                        {{ mb_substr(auth('quickreads-admin')->user()->name, 0, 1) }}
                    </span>
                    <span>
                        {{ auth('quickreads-admin')->user()->name }}
                        <small>
                            Administrator
                        </small>
                    </span>
                </div>
            </header>
            <main id="main" tabindex="-1">
                @if(session('success'))
                    <div class="notice success" role="status">
                        @include('projects.quickreads.admin.icon', ['name' => 'check'])
                        <span>
                            {{ session('success') }}
                        </span>
                        <button type="button" class="dismiss" aria-label="Dismiss message">
                            ×
                        </button>
                    </div>
                @endif
                @if($errors->any())
                    <div class="notice error" role="alert">
                        <div>
                            <strong>
                                Please check the following:
                            </strong>
                            <ul>
                                @foreach($errors->all() as $error)
                                    <li>
                                        {{ $error }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
                @yield('content')
                <footer class="page-footer">
                    <span>
                        Quickreads
                        <span class="muted">
                            /
                        </span>
                        Administration
                    </span>
                    <span>
                        A little reading goes a long way.
                    </span>
                </footer>
            </main>
        </div>
    </body>
</html>
