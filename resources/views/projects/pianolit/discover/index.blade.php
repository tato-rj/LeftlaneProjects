@extends('projects/pianolit/layouts/app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('projects/pianolit/components/breadcrumb', [
    'title' => 'Discover',
    'description' => 'Explore the playlists with the api',
    'path' => 'api/discover'])
  </div>
  
  <div class="text-center">
    <a href="{{url()->current()}}?api" target="_blank" class="link-default"><small>See JSON response</small></a>
  </div>

  <div class="row">
   <div class="col-lg-6 col-md-8 col-10 mx-auto mb-5">
    @component('projects/pianolit/components/swiper', ['title' => 'Periods'])
      @foreach($periods as $period)
      <div>
        <div class="d-flex align-items-end rounded bg-advanced mx-2 py-2 px-3" style="height: 120px; width: 120px">
          <div>
            <p class="m-0 elipsis" style="max-width: 90px;">{{ucfirst($period->name)}}</p>
            <span><small>{{$period->pieces->count()}} {{str_plural('piece', $period->pieces->count())}}</small></span>
          </div>
        </div>
      </div>
      @endforeach
    @endcomponent

    @component('projects/pianolit/components/swiper', ['title' => 'Composers'])
      @foreach($composers as $composer)
      <div>
        <div class="d-flex align-items-end rounded bg-intermediate mx-2 py-2 px-3" style="height: 120px; width: 120px">
          <div>
            <p class="m-0 elipsis" style="max-width: 90px;">{{$composer->short_name}}</p>
            <span><small>{{$composer->pieces_count}} {{str_plural('piece', $composer->pieces_count)}}</small></span>
          </div>
        </div>
      </div>
      @endforeach
    @endcomponent

    @component('projects/pianolit/components/swiper', ['title' => 'Improve your'])
      @foreach($improve as $tag)
      <div>
        <div class="d-flex align-items-end rounded bg-beginner mx-2 py-2 px-3" style="height: 120px; width: 120px">
          <div>
            <p class="m-0 elipsis" style="max-width: 90px;">{{ucfirst($tag->name)}}</p>
            <span><small>{{$tag->pieces->count()}} {{str_plural('piece', $tag->pieces->count())}}</small></span>
          </div>
        </div>
      </div>
      @endforeach
    @endcomponent

    @component('projects/pianolit/components/swiper', ['title' => 'Coming from'])
      @foreach($countries as $country)
      <div>
        <div class="d-flex align-items-end rounded bg-intermediate mx-2 py-2 px-3" style="height: 120px; width: 120px">
          <div>
            <p class="m-0 elipsis" style="max-width: 90px;">{{ucfirst($country->name)}}</p>
            <span><small>{{$country->pieces->count()}} {{str_plural('piece', $country->pieces->count())}}</small></span>
          </div>
        </div>
      </div>
      @endforeach
    @endcomponent

    @component('projects/pianolit/components/swiper', ['title' => 'Levels'])
      @foreach($levels as $level)
      <div>
        <div class="d-flex align-items-end rounded bg-beginner mx-2 py-2 px-3" style="height: 120px; width: 120px">
          <div>
            <p class="m-0 elipsis" style="max-width: 90px;">{{ucfirst($level->name)}}</p>
            <span><small>{{$level->pieces->count()}} {{str_plural('piece', $level->pieces->count())}}</small></span>
          </div>
        </div>
      </div>
      @endforeach
    @endcomponent

    @component('projects/pianolit/components/swiper', ['title' => 'Foundation'])
      @foreach($foundation as $playlist)
      <div>
        <div class="d-flex align-items-end rounded bg-advanced mx-2 py-2 px-3" style="height: 120px; width: 120px">
          <div>
            <p class="m-0 elipsis" style="max-width: 90px;">{{ucfirst($playlist->name)}}</p>
            <span><small>{{$playlist->pieces->count()}} {{str_plural('piece', $playlist->pieces->count())}}</small></span>
          </div>
        </div>
      </div>
      @endforeach
    @endcomponent

    @component('projects/pianolit/components/swiper', ['title' => 'Trending'])
      @foreach($trending as $piece)
      <a href="{{route('piano-lit.pieces.edit', $piece->id)}}" class="link-none">
        <div class="d-flex justify-content-between bg-light flex-column mx-2 py-2 px-3" style="height: 120px; width: 180px">
          <div>
            <p class="m-0 clamp-2" style="max-width: 90px;">{{ucfirst($piece->short_name)}}</p>
            <span><small>by {{$piece->composer->short_name}}</small></span>  
          </div>
          <div><small><i class="fas fa-eye mr-1"></i>{{$piece->views}} {{ str_plural('view', $piece->views) }}</small></div>
        </div>
      </a>
      @endforeach
    @endcomponent

    @component('projects/pianolit/components/swiper', ['title' => 'Latest'])
      @foreach($latest as $piece)
      <a href="{{route('piano-lit.pieces.edit', $piece->id)}}" class="link-none">
        <div class="d-flex justify-content-between bg-light flex-column mx-2 py-2 px-3" style="height: 120px; width: 180px">
          <div>
            <p class="m-0 clamp-2" style="max-width: 90px;">{{ucfirst($piece->short_name)}}</p>
            <span><small>by {{$piece->composer->short_name}}</small></span>  
          </div>
          <div><small><i class="fas fa-calendar-alt mr-1"></i>{{$piece->created_at->diffForHumans()}}</small></div>
        </div>
      </a>
      @endforeach
    @endcomponent

    @component('projects/pianolit/components/swiper', ['title' => 'Most popular'])
      @foreach($famous as $piece)
      <a href="{{route('piano-lit.pieces.edit', $piece->id)}}" class="link-none">
        <div class="d-flex justify-content-between bg-light flex-column mx-2 py-2 px-3" style="height: 120px; width: 180px">
          <div>
            <p class="m-0 clamp-2" style="max-width: 90px;">{{ucfirst($piece->short_name)}}</p>
            <span><small>by {{$piece->composer->short_name}}</small></span>  
          </div>
          <div class="text-{{strtolower($piece->level)}}"><small>{{$piece->level}}</small></div>
        </div>
      </a>
      @endforeach
    @endcomponent

    @component('projects/pianolit/components/swiper', ['title' => 'Flashy pieces'])
      @foreach($flashy as $piece)
      <a href="{{route('piano-lit.pieces.edit', $piece->id)}}" class="link-none">
        <div class="d-flex justify-content-between bg-light flex-column mx-2 py-2 px-3" style="height: 120px; width: 180px">
          <div>
            <p class="m-0 clamp-2" style="max-width: 90px;">{{ucfirst($piece->short_name)}}</p>
            <span><small>by {{$piece->composer->short_name}}</small></span>  
          </div>
          <div class="text-{{strtolower($piece->level)}}"><small>{{$piece->level}}</small></div>
        </div>
      </a>
      @endforeach
    @endcomponent

    @component('projects/pianolit/components/swiper', ['title' => 'Learning ornaments'])
      @foreach($ornaments as $piece)
      <a href="{{route('piano-lit.pieces.edit', $piece->id)}}" class="link-none">
        <div class="d-flex justify-content-between bg-light flex-column mx-2 py-2 px-3" style="height: 120px; width: 180px">
          <div>
            <p class="m-0 clamp-2" style="max-width: 90px;">{{ucfirst($piece->short_name)}}</p>
            <span><small>by {{$piece->composer->short_name}}</small></span>  
          </div>
          <div class="text-{{strtolower($piece->level)}}"><small>{{$piece->level}}</small></div>
        </div>
      </a>
      @endforeach
    @endcomponent

    </div>
  </div>
</div>

@endsection
