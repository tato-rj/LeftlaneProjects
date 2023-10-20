<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{config('app.projects.videouploader.name')}}</title>

        <meta name="csrf-token" content="{{ csrf_token() }}">

        <link rel="apple-touch-icon" sizes="57x57" href="{{asset('favicon/videouploader/apple-icon-57x57.png')}}">
        <link rel="apple-touch-icon" sizes="60x60" href="{{asset('favicon/videouploader/apple-icon-60x60.png')}}">
        <link rel="apple-touch-icon" sizes="72x72" href="{{asset('favicon/videouploader/apple-icon-72x72.png')}}">
        <link rel="apple-touch-icon" sizes="76x76" href="{{asset('favicon/videouploader/apple-icon-76x76.png')}}">
        <link rel="apple-touch-icon" sizes="114x114" href="{{asset('favicon/videouploader/apple-icon-114x114.png')}}">
        <link rel="apple-touch-icon" sizes="120x120" href="{{asset('favicon/videouploader/apple-icon-120x120.png')}}">
        <link rel="apple-touch-icon" sizes="144x144" href="{{asset('favicon/videouploader/apple-icon-144x144.png')}}">
        <link rel="apple-touch-icon" sizes="152x152" href="{{asset('favicon/videouploader/apple-icon-152x152.png')}}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{asset('favicon/videouploader/apple-icon-180x180.png')}}">
        <link rel="icon" type="image/png" sizes="192x192"  href="{{asset('favicon/videouploader/android-icon-192x192.png')}}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{asset('favicon/videouploader/favicon-32x32.png')}}">
        <link rel="icon" type="image/png" sizes="96x96" href="{{asset('favicon/videouploader/favicon-96x96.png')}}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{asset('favicon/videouploader/favicon-16x16.png')}}">
        <link rel="manifest" href="{{asset('favicon/videouploader/manifest.json">
                                ')}}        <meta name="msapplication-TileColor" content="#ffffff">
        <meta name="msapplication-TileImage" content="{{asset('favicon/videouploader/ms-icon-144x144.png')}}">
        <meta name="theme-color" content="#ffffff">

        <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
        
        <style type="text/css">
            .alert {
                margin-left: 16px;
            }
            
            .screen-lock-overlay {
              position: fixed;
              top: 0;
              left: 0;
              background: rgba(0,0,0,0.7);
              z-index: 10000;
              width: 100%;
              height: 100vh;
              transition: .2s;
            }

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

            .opacity-8 {
                opacity: .8;
            }
            
            .opacity-6 {
                opacity: .6;
            }

            .opacity-4 {
                opacity: .4;
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

        @include('projects.videouploader.records.overlay')
        
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js"></script>
        
        <script type="text/javascript">
        $('form[method="POST"]').on('submit', function() {
            $(this).find('button[type="submit"]').prop('disabled', true);
        });
        </script>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/resumable.js/1.0.3/resumable.min.js"></script>
        @auth
        <script type="text/javascript">
        let $progressBar = $('.progress-bar');
        let $uploadOverlay = $('#upload-overlay');
        let $uploadButton = $('#choose-video');
        let $confirmModal = $('#confirm-modal');
        let $confirmButton = $('#confirm-button');
        let resumable = new Resumable({
            target: '{{ route('videouploader.upload') }}',
            query:{
                _token:'{{ csrf_token() }}',
                secret:'{{auth()->user()->tokens()->exists() ? auth()->user()->tokens->first()->name : null}}',
                origin: 'local',
                user_id: Math.floor(Math.random() * 100) + 1,
                piece_id: Math.floor(Math.random() * 100) + 1,
                email: 'test@email.com'
            },
            fileType: ['mp4', 'mov', 'MOV'],
            maxFiles: 1,
            maxFileSize: 500000000,
            headers: {
                'Accept' : 'application/json'
            },
            testChunks: false,
            throttleProgressCallbacks: 1,
        });

        resumable.assignBrowse($uploadButton[0]);

        $confirmButton.on('click', function() {
            if (resumable.files.length) {
                $(this).prop('disabled', true);
                resumable.opts.query.notes = $('textarea[name="notes"]').val();
                resumable.upload();
                $uploadOverlay.show();
            }
        });

        resumable.on('fileAdded', function (file) {
            showProgress();
            startTime = moment();
            $confirmModal.modal('show');
        });

        resumable.on('fileProgress', function (file) {
            let percentage = Math.floor(file.progress() * 100);

            updateProgress(percentage);
            nextLoadingText(percentage);
        });

        resumable.on('fileSuccess', function (file, response) {
            setTimeout(function() {
                completeProgress();

                setTimeout(function() {
                    location.reload();
                }, 2000);
            }, 1000);
        });

        resumable.on('fileError', function (file, response) {
            console.log(response);
            alert('File uploading error.');
        });

        $confirmModal.on('hidden.bs.modal', function() {
            resumable.cancel();
        });
        </script>
        <script type="text/javascript">
        let $progress = $('.progress');
        let $loadingText = $('#loading-text');

        function showProgress() {
            $progress.find('.progress-bar').css('width', '0%');
            $progress.find('.progress-bar').html('0%');
            $progress.find('.progress-bar').removeClass('bg-success');
            $progress.show();
        }

        function updateProgress(value) {
            $progress.find('.progress-bar').css('width', `${value}%`);
            $progress.find('.progress-bar').html(`${value}%`);
        }

        function hideProgress() {
            $progress.hide();
        }

        let startTime;
        let canChangeSentence = true;

        function nextLoadingText(percentage) {
            if (! canChangeSentence)
                return null;

            let array = $loadingText.data('sentences');
            let index = Math.floor(percentage/Math.floor(100 / array.length));

            if (percentage > 90) {
                $loadingText.text(array.pop());

                canChangeSentence = false;
            } else {
                if (moment().diff(startTime, 'seconds') % 4 === 0) {
                    $loadingText.text(array[index]);
                }
            }
        }

        function completeProgress() {
            $progressBar.removeClass('progress-bar-striped progress-bar-animated')
                        .addClass('bg-success')
                        .html('<i class="fa-solid fa-check fa-lg"></i>')
                        .parent()
                        .addClass('animate__rubberBand');

            $loadingText.text('Done!');
        }
        </script>

        <script type="text/javascript">
        </script>
        @endauth
        @stack('scripts')
    </body>


</html>
