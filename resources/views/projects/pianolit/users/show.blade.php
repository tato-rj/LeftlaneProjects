@extends('projects/pianolit/layouts/app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('projects/pianolit/components/breadcrumb', [
    'title' => 'Users',
    'description' => "$user->first_name's profile"])

    <div class="text-center">
      <a href="{{route('piano-lit.api.user', $user->id)}}" target="_blank" class="link-default"><small>See JSON response</small></a>
    </div>  
   
    <div class="d-flex my-4 mx-3">
      <div class="mr-5">
        <img src="https://api.adorable.io/avatars/236/{{$user->email}}" class="rounded-circle" style="width: 160px">
      </div>

      <div class="mr-5">
        <div>
          <label class="text-brand m-0"><small>Name</small></label>
          <p>{{$user->full_name}}</p>
        </div>
        <div>
          <label class="text-brand m-0"><small>E-mail</small></label>
          <p>{{$user->email}}</p>
        </div>
      </div>
      
      <div class="mr-5">
        <div>
          <label class="text-brand m-0"><small>Gender</small></label>
          <p>{{ucfirst($user->gender)}}</p>
        </div>
        <div>
          <label class="text-brand m-0"><small>Language</small></label>
          <p>{{Locale::getDisplayName($user->locale)}}</p>
        </div>
      </div>

      <div>
        <div>
          <label class="text-brand m-0"><small>Status</small></label>
          @if($user->is_active)
          <p class="text-success">Active</p>
          @else
          <p class="text-danger">Inactive</p>
          @endif
        </div>
      </div>
    </div>

    <div class="row mx-3">
      <div class="col-lg-6 col-sm-10 col-12">
        <p class="text-muted">
          <strong>{{$user->first_name}} has {{$user->favorites->count()}} {{str_plural('favorite', $user->favorites->count())}}</strong>
        </p>
        @if($user->favorites->count() > 0)
        <ul class="list-style-none pl-2">
          @foreach($user->favorites as $piece)
          <li class="mb-2">
            <a href="{{route('piano-lit.pieces.edit', $piece)}}">
              <i class="fas fa-caret-right mr-2"></i>{{$piece->long_name}}
            </a>
          </li>
          @endforeach
        </ul>
        @endif
      </div>
      <div class="col-lg-6 col-sm-10 col-12">
        <p class="text-muted">
          <strong>Suggested pieces</strong> | <a href="{{route('piano-lit.api.suggestions', $user->id)}}" target="_blank" class="link-default"><small>See JSON response</small></a>
        </p>
        <ul class="list-style-none pl-2">
          @foreach($user->suggestions(10) as $piece)
          <li class="mb-2">
            <a href="{{route('piano-lit.pieces.edit', $piece)}}">
              <i class="fas fa-caret-right mr-2"></i>{{$piece->long_name}}
            </a>
          </li>
          @endforeach
        </ul>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')

@endsection