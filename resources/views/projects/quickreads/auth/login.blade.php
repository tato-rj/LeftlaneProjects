<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quickreads | Admin</title>
    <link rel="stylesheet" type="text/css" href="{{asset('css/app.css')}}">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.10/css/all.css" integrity="sha384-+d0P83n9kaQMCwj8F4RJB66tzIwOKmrdb46+porD/OvrJ+37WqIM7UoBtwHO6Nlg" crossorigin="anonymous">
    <style type="text/css">
        body {
            background: #161d25;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row align-items-center full-height">
            <div class="col-lg-4 col-md-6 col-sm-8 col-xs-10 mx-auto text-white">
                <form class="form-horizontal" method="POST" action="{{ route('quickreads.admin.login.submit') }}">
                    {{ csrf_field() }}
                    <div class="col-lg-5 col-md-6 col-sm-6 col-xs-6 mx-auto mb-4">
                        <img class="w-100 mb-3" src="{{asset('images/icon.svg')}}">
                        <h5>QUICKREADS</h5>
                    </div>
                    
                    <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                        <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" placeholder="E-mail" required autofocus>
                        @if ($errors->has('email'))
                        <span class="help-block">
                            <strong>{{ $errors->first('email') }}</strong>
                        </span>
                        @endif

                    </div>
                    <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                        <input id="password" type="password" class="form-control" name="password" placeholder="Password" required>

                        @if ($errors->has('password'))
                        <span class="help-block">
                            <strong>{{ $errors->first('password') }}</strong>
                        </span>
                        @endif
                    </div>
                    <div class="form-group">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}> Remember Me
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-default btn-block">
                            Login
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script type="text/javascript" src="{{asset('js/app.js')}}"></script>
</body>
</html>
