<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{config('app.projects.videouploader.name')}}</title>

        <meta name="csrf-token" content="{{ csrf_token() }}">

        <link rel="icon" href="{{asset('images/projects/videouploader/favicon.png')}}">

        <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
        
        <style type="text/css">
            .border-x-0 {
                border-left: 0;
                border-right: 0;
            }
            .border-s-0 {
                border-left: 0;
            }
            .btn-raw {
                background: none;
                border: none;
                padding: 0;
            }
            .alert {
                position: fixed;
                bottom: 0;
                right: 16px;
            }
            
            button:focus,button:active,.btn:focus,.btn:active {
               outline: none !important;
               box-shadow: none !important;
            }

            .accordion-item,.btn,.form-control {
                border-radius: 0 !important;
            }

            .accordion-button:not(.collapsed) {
                background-color: inherit;
                color: inherit;
            }

            .link-none,.link-none:hover {
                color: inherit;
                text-decoration: none;
            }
        </style>

        @stack('header')
    </head>
    <body>
        <div class="container mb-3 pt-4">
            <div class="d-flex justify-content-between align-items-center">
                <a class="link-none" href="{{route('videouploader.home')}}">
                    <div class="d-flex align-items-center">
                        <img src="{{asset('images/projects/videouploader/logo.svg')}}" style="width: 36px; margin-bottom: 9px;" class="me-2">
                        <h2 class="">Video Uploader <span class="text-muted h3">for PianoLIT</span></h2>
                    </div>
                </a>

                <div>
                    @auth
                    <a class="nav-link bg-outline-secondary rounded-pill px-4 py-1 mb-3" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" href="">
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>Logout
                    </a>
                    @else
                    <a href="{{route('login')}}">Login</a>
                    @endauth
                </div>
            </div>
        </div>

        @yield('content')

        @if($message = session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          {{$message}}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        @endif

        @if($errors->first())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          {{$errors->first()}}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif
        
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js"></script>
        
        @stack('scripts')
    </body>


</html>
