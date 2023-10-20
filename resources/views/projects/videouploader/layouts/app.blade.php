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
            .modal-body {
                padding-top: 0 !important;
            }
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
                    <img src="{{asset('images/projects/videouploader/logo.svg')}}" style="width: 54px;">
                </a>

                @include('projects.videouploader.layouts.nav')
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
        
        <script type="text/javascript">
        $('form[method="POST]').on('submit', function() {
            $(this).find('button[type="submit"]').prop('disabled', true);
        });
        </script>
        @stack('scripts')
    </body>


</html>
