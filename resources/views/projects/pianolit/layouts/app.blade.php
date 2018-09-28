<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>PianoLIT | Admin</title>
        <link rel="stylesheet" type="text/css" href="{{asset('css/theme.min.css')}}">
        <link rel="stylesheet" type="text/css" href="{{asset('css/tables.css')}}">
        <link rel="stylesheet" type="text/css" href="{{asset('css/pianolit.css')}}?version=11">
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.10/css/all.css" integrity="sha384-+d0P83n9kaQMCwj8F4RJB66tzIwOKmrdb46+porD/OvrJ+37WqIM7UoBtwHO6Nlg" crossorigin="anonymous">

        <script>
            window.app = <?php echo json_encode([
                'csrfToken' => csrf_token(),
                'url' => \Request::root(),
                'user' => auth()->guard('pianolit-admin')->user()
            ]); ?>
        </script>
        @yield('head')
    </head>
<body class="fixed-nav sticky-footer bg-dark" id="page-top">

  @include('projects/pianolit/components/nav')

  @yield('content')

  @include('projects/pianolit/components/footer')
  @include('projects/pianolit/components/modals/logout')

  @if($message = session('success'))
    @include('projects/pianolit/components/alerts/success')
  @endif

  @if($message = session('error'))
    @include('projects/pianolit/components/alerts/error')
  @endif

  <script type="text/javascript" src="{{asset('js/app.js')}}"></script>

  <script type="text/javascript" src="{{asset('js/vendor/jquery.easing.min.js')}}"></script>
  <script type="text/javascript" src="{{asset('js/vendor/Chart.min.js')}}"></script>
  <script type="text/javascript" src="{{asset('js/vendor/jquery.dataTables.js')}}"></script>

  <script type="text/javascript" src="{{asset('js/theme.min.js')}}"></script>

  <script type="text/javascript" src="{{asset('js/tables.min.js')}}"></script>
  {{-- <script type="text/javascript" src="{{asset('js/charts.min.js')}}"></script> --}}

  <script type="text/javascript">
    $('.alert .fa').on('click', function(){
      $(this).parent().parent().remove();
    });
  </script>
  @yield('scripts')
</body>
    
</html>
