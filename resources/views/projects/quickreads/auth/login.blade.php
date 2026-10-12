<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title>
            Sign in · Quickreads
        </title>
        <link rel="stylesheet" href="{{ asset('css/quickreads-admin.css') }}?v={{ filemtime(public_path('css/quickreads-admin.css')) }}">
    </head>
    <body class="login-body">
        <main class="login-layout">
            <section class="login-intro">
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
                <div>
                    <p class="eyebrow">
                        A GOOD DAY FOR A GOOD STORY
                    </p>
                    <h1>
                        Small stories.
                        <br>
                        Big possibilities.
                    </h1>
                    <p>
                        A thoughtful space to manage your library
                        <br>
                        and the people who read it.
                    </p>
                </div>
                <span>
                    A little reading goes a long way.
                </span>
            </section>
            <section class="login-form">
                <p class="eyebrow">
                    WELCOME BACK
                </p>
                <h2>
                    Open your workspace.
                </h2>
                <p class="muted">
                    Sign in with your administrator account.
                </p>
                @if($errors->any())
                    <div class="notice error" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif
                <form method="POST" action="{{ route('quickreads.admin.login.submit') }}">
                    @csrf
                    <div class="field">
                        <label for="email">
                            Email address
                        </label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required autocomplete="username" autofocus>
                    </div>
                    <div class="field">
                        <label for="password">
                            Password
                        </label>
                        <input type="password" name="password" id="password" required autocomplete="current-password">
                    </div>
                    <label class="checkbox">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                        Keep me signed in
                    </label>
                    <button class="button primary" type="submit">
                        Sign in
                        @include('projects.quickreads.admin.icon', ['name' => 'arrow'])
                    </button>
                </form>
            </section>
        </main>
    </body>
</html>
