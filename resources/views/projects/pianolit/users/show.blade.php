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
   
    <div class="d-flex justify-content-between my-4 mx-3">
      <div class="d-flex">
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
            <label class="text-brand m-0"><small>Language</small></label>
            <p>{{Locale::getDisplayName($user->locale)}}</p>
          </div>
          <div>
            <label class="text-brand m-0"><small>Status</small></label>
            @if($user->is_active)
            <p class="text-success">Active</p>
            @else
            <p class="text-danger">
              @if($user->trial_ends_at->gt(Carbon\Carbon::now()))
               Trial ends in {{$user->trial_ends_at->diffForHumans()}} ({{$user->trial_ends_at->toFormattedDateString()}})
              @else
               Inactive
              @endif
            </p>
            @endif
          </div>
        </div>
      </div>
      <div>
        <a href="" data-name="{{$user->full_name}}" data-url="/piano-lit/users/{{$user->id}}" data-toggle="modal" data-target="#delete-modal" class="text-danger delete">Delete user's profile</a>
      </div>
    </div>

    <div class="row mb-3">
      <div class="col-lg-6 col-md-6 col-sm-12 col-12 p-3">
        <div class="text-center rounded bg-light px-3 py-2">
          <p class="text-muted mb-2 pb-2 border-bottom"><strong>My age range is...</strong></p>
          <p class="m-0">{{$user->age_range}}</p>          
        </div>
      </div>
      <div class="col-lg-6 col-md-6 col-sm-12 col-12 p-3">
        <div class="text-center rounded bg-light px-3 py-2">
          <p class="text-muted mb-2 pb-2 border-bottom"><strong>I consider my piano experience to be...</strong></p>
          <p class="m-0">{{ucfirst($user->experience)}}</p>          
        </div>
      </div>
      <div class="col-lg-6 col-md-6 col-sm-12 col-12 p-3">
        <div class="text-center rounded bg-light px-3 py-2">
          <p class="text-muted mb-2 pb-2 border-bottom"><strong>The piece I like the most is...</strong></p>
          <p class="m-0 clamp-1">{{\App\Projects\PianoLit\Piece::find($user->preferred_piece_id)->medium_name}}</p>          
        </div>        
      </div>
      <div class="col-lg-6 col-md-6 col-sm-12 col-12 p-3">
        <div class="text-center rounded bg-light px-3 py-2">
          <p class="text-muted mb-2 pb-2 border-bottom"><strong>I came to PianoLit because I'm a...</strong></p>
          <p class="m-0">{{ucfirst($user->occupation)}}</p>          
        </div>            
      </div>
    </div>
    
    <div class="row mb-4 mx-3">
      <div class="col-lg-6 col-sm-10 col-12">
        <p class="text-muted">
          <strong>{{$user->first_name}} has {{$user->favorites->count()}} {{str_plural('favorite', $user->favorites->count())}}</strong> | <a class="link-default" href="" data-toggle="modal" data-target="#edit-favorites"><small>Edit selections</small></a>

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
        <p class="text-muted m-0">
          <strong>Suggested pieces</strong> | <a href="{{route('piano-lit.api.suggestions', $user->id)}}" target="_blank" class="link-default"><small>See JSON response</small></a>
        </p>

        <div class="mt-2 mb-3 ml-2">
          <span class="text-muted"><small>Top tags: </small></span>
          @foreach($user->tags() as $tag)
          <span class="badge badge-pill badge-light">{{$tag}}</span>
          @endforeach
        </div>
        
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

@include('projects/pianolit/components/modals/delete', ['model' => 'user'])
@include('projects/pianolit/users/favorites')

@endsection

@section('scripts')
<script type="text/javascript">
$('.piece').on('click', function() {
  $piece = $(this);
  $heart = $piece.find('.fa-heart');

  $heart.toggleClass('fas far');

  $.post($piece.attr('data-url'), {'piece_id': $piece.attr('data-id')}, 
    function(response){
      console.log(response);
    if(response.passes) {
      // console.log(response);
    } else {
      // console.log('We couldn\'t save your feedback right now.');
    }
  });
});
</script>
@endsection