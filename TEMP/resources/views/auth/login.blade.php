@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row align-items-center full-height">
        <div class="col-lg-4 col-md-6 col-sm-8 col-xs-10 mx-auto text-white">
            <form class="form-horizontal" method="POST" action="{{ route('login') }}">
                {{ csrf_field() }}
                <div class="col-lg-5 col-md-6 col-sm-6 col-xs-6 mx-auto mb-4">
                    <img class="w-100" src="{{asset('images/icon.svg')}}">
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
                    {{-- <a class="btn btn-link btn-block" href="{{ route('password.request') }}">Forgot Your Password?</a> --}}
                </div>
            </form>

        </div>
    </div>
</div>
</div>
@endsection
