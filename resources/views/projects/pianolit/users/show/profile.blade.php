<div class="tab-pane fade show active" id="profile">
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
      <div class="d-flex align-items-center">
        <p class="text-muted m-0">
          <strong>Suggested pieces</strong> | </p>  
          <form method="POST" action="{{route('piano-lit.api.suggestions')}}" target="_blank">
            <input type="hidden" name="user_id" value="{{$user->id}}">
            <button type="submit" class="text-brand ml-1 btn btn-link p-0 cursor-pointer"><small>See JSON response</small></button>
          </form>
      </div>

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