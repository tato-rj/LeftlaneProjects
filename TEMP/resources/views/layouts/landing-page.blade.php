<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{$title}}</title>
    <!-- Font -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css?family=Rubik:300,400,500" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{asset('pianolit/css/bootstrap.min.css')}}">
    <!-- Themify Icons -->
    <link rel="stylesheet" href="{{asset('pianolit/css/themify-icons.css')}}">
    <!-- Owl carousel -->
    <link rel="stylesheet" href="{{asset('pianolit/css/owl.carousel.min.css')}}">
    <!-- Main css -->
    <link href="{{asset('pianolit/css/theme.css')}}" rel="stylesheet">

    @yield('header')
</head>
<body>

    @yield('content')

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}"></script>
    @yield('scripts')
</body>
</html>
