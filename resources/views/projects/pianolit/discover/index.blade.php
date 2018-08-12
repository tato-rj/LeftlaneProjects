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
    <a href="{{route('piano-lit.api.discover')}}?api" target="_blank" class="link-default"><small>See JSON response</small></a>
  </div>

  <div class="row">
   <div class="col-lg-6 col-md-8 col-10 mx-auto mb-5">

    @foreach($collection as $playlist)
      @component('projects/pianolit/components/swiper', ['title' => $playlist['title']])
        @foreach($playlist['content'] as $model)
          <form name="{{$model->type == 'piece' ? 'piece_'.snake_case($model->id) : snake_case($model->name)}}_form" method="POST" action="{{$model->source}}" target="{{$model->type == 'piece' ? '_blank' : null}}">
            <input type="hidden" name="search" value="{{$model->type == 'piece' ? $model->id : $model->name}}">
            <input type="hidden" name="global">
            <input type="hidden" name="discover">
            @include('projects/pianolit/discover/card')
          </form>
        @endforeach
      @endcomponent
    @endforeach

    </div>
  </div>
</div>

@if(! empty($pieces))
@component('projects/pianolit/components/modals/results')
  @include('projects/pianolit/search/results')
@endcomponent
@endif

@endsection

@section('scripts')
<script type="text/javascript">
if ($('#results-modal').length > 0)
    $('#results-modal').modal('show');
</script>
@endsection