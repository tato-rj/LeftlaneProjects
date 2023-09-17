@extends('projects.videouploader.layouts.app')

@section('content')
<div class="container">
    <div class="col-lg-4 col-6">
        <div class="mb-3">
            <form method="POST" action="{{route('videouploader.login')}}">
                @csrf
                <input placeholder="Email" required type="text" name="email" class="form-control mb-3">
                <input placeholder="Password" required type="password" name="password" class="form-control mb-3">
                <button class="btn btn-primary">Login</button>
            </form>
        </div>
    </div>
</div>
@endsection