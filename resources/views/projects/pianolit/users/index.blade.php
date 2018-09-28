@extends('projects/pianolit/layouts/app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('projects/pianolit/components/breadcrumb', [
    'title' => 'Users',
    'description' => 'View detailed information about the users'])

    <div class="row">
      <div class="col-12 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <button type="button" class="btn btn-sm btn-default mr-3" data-toggle="modal" data-target="#add-modal">
            <i class="fas fa-plus mr-2"></i>Create a new user
          </button>
    
          <form method="POST" action="{{route('piano-lit.users.subscription.verify-all')}}">
            {{csrf_field()}}
            <input type="hidden" name="admin_id" value="{{auth()->guard('pianolit-admin')->user()->id}}">
            <button class="btn btn-sm btn-success"><i class="fas fa-clipboard-check mr-2"></i>Validate all subscriptions</button>
          </form>

        </div>
        <div>
          @include('projects/pianolit/components/filters', ['filters' => []])
        </div>
      </div>
    </div>

    <div class="row my-3">
      <div class="col-12 text-center">
        <p class="text-center"><small>Showing {{$users->count()}} of {{$users->total()}}</small></p>
      </div>
      @foreach($users as $user)
      <div class="col-12 mb-2">
        <a href="/piano-lit/users/{{$user->id}}" class="link-none">
          <div class="d-flex align-items-center bg-light text-muted px-3 py-2 badge-pill hover-shadow-light t-2">
            
            @include('projects/pianolit/users/status-icon/'.$user->getStatus())

            <div class="px-3" style="flex-grow: 2">
              <span>
                <strong>{{$user->full_name}}</strong> | <small><i>signed up on {{$user->created_at->toFormattedDateString()}}</i></small>
              </span>
            </div>

            <div>
              @if($user->subscription()->exists())
                @if($user->subscription->expired())
                <span class="text-muted"><i><small>validated {{$user->subscription->validated_at->diffForHumans()}}</small></i></span>
                @else
                <div><i class="fas fa-credit-card"></i></div>
                @endif
              @endif
            </div>
          </div>
        </a>
      </div>
      @endforeach
    </div>

    {{-- PAGINATION --}}
    <div class="row mb-3">
          <div class="d-flex align-items-center w-100 justify-content-center my-4">
        {{ $users->links() }}    
        </div>
    </div>

  </div>
</div>

@component('projects/pianolit/components/modals/add', ['model' => 'user'])
<form method="POST" action="/piano-lit/api/users">
  <input type="hidden" name="from_backend">
  {{-- First Name --}}
  <div class="form-group">
    <input type="text" class="form-control" name="first_name" placeholder="First name" value="{{ old('first_name') }}" >
  </div>
  {{-- Last Name --}}
  <div class="form-group">
    <input type="text" class="form-control" name="last_name" placeholder="Last name" value="{{ old('last_name') }}" >
  </div>
  {{-- Email --}}
  <div class="form-group">
    <input type="text" class="form-control" name="email" placeholder="E-mail" value="{{ old('email') }}" >
  </div>
  {{-- Password --}}
  <div class="form-group">
    <input type="password" placeholder="Password" class="form-control" name="password" >
  </div>
  <div class="form-group">
    <input type="password" placeholder="Confirm your password" class="form-control" name="password_confirmation" >
  </div>

  {{-- About --}}
  <div class="form-row form-group">
    <div class="col">
      <select class="form-control {{$errors->has('age_range') ? 'is-invalid' : ''}}" name="age_range">
        <option selected disabled>Age range</option>
        @foreach(\App\Projects\PianoLit\User::ageRange() as $age)
        <option value="{{$age}}" {{ old('age_range') == $age ? 'selected' : ''}}>{{$age}}</option>
        @endforeach
      </select>
      @include('projects/pianolit/components/feedback', ['field' => 'age_range'])
    </div>
    <div class="col">
      <select class="form-control {{$errors->has('experience') ? 'is-invalid' : ''}}" name="experience">
        <option selected disabled>Experience</option>
        @foreach(\App\Projects\PianoLit\User::experience() as $experience)
        <option value="{{$experience}}" {{ old('experience') == $experience ? 'selected' : ''}}>{{ucfirst($experience)}}</option>
        @endforeach
      </select>
      @include('projects/pianolit/components/feedback', ['field' => 'experience'])
    </div>
  </div>
  <div class="form-row form-group">
    <div class="col">
      <select class="form-control {{$errors->has('preferred_piece_id') ? 'is-invalid' : ''}}" name="preferred_piece_id">
        <option selected disabled>Preferred piece</option>
        @foreach(\App\Projects\PianoLit\User::preferredPieces() as $piece)
        <option value="{{$piece->id}}" {{ old('preferred_piece_id') == $piece->id ? 'selected' : ''}}>{{$piece->medium_name}}</option>
        @endforeach
      </select>
      @include('projects/pianolit/components/feedback', ['field' => 'preferred_piece_id'])
    </div>
    <div class="col">
      <select class="form-control {{$errors->has('occupation') ? 'is-invalid' : ''}}" name="occupation">
        <option selected disabled>Occupation</option>
        @foreach(\App\Projects\PianoLit\User::occupation() as $occupation)
        <option value="{{$occupation}}" {{ old('occupation') == $occupation ? 'selected' : ''}}>{{ucfirst($occupation)}}</option>
        @endforeach
      </select>
      @include('projects/pianolit/components/feedback', ['field' => 'occupation'])
    </div>
  </div>
  <input type="hidden" name="locale" value="en_US">
@endcomponent

@if($errors->any())
  @include('projects/pianolit/components/alerts/error')
@endif

@endsection

@section('scripts')

@endsection