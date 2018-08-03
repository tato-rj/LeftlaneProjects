@extends('projects/pianolit/layouts/app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('projects/pianolit/components/breadcrumb', [
    'title' => 'Tour',
    'description' => 'Explore the tour with the api',
    'path' => 'pieces/tour'])
    
  </div>

  <div class="row">
    <div class="col-lg-6 col-md-8 col-10 mx-auto mb-5">

        <div id="app-intro" class="carousel slide" data-ride="carousel" data-interval="false">
        <form method="POST" action="{{route('piano-lit.api.tour')}}">
          {{csrf_field()}}
          <input type="hidden" name="tour">
          <input type="hidden" name="search" value="">
        </form>
          <div class="carousel-inner">
            
              <div class="carousel-item row active">
                <div class="col-8 mx-auto text-center my-2">
                  <div>
                    <h5 class="text-brand my-4">Select the level</h5>
                  </div>
                  
                  @foreach(\App\Projects\PianoLit\Tag::levels()->pluck('name') as $tag)
                  <button class="tag-button btn btn-light py-3 btn-block"><strong>{{ucfirst($tag)}}</strong></button>
                  @endforeach
                </div>
              </div>
              <div class="carousel-item text-center">
                <div>
                  <h5 class="text-brand my-4">Select the mood</h5>
                </div>
                <div class="row no-gutters"> 
                  @foreach(\App\Projects\PianoLit\Tag::special()->pluck('name') as $tag)
                  <div class="col-6 mb-2 px-1">
                    <button class="tag-button btn btn-light py-3 btn-block"><strong>{{ucfirst($tag)}}</strong></button>
                  </div>
                  @endforeach
                </div>
              </div>
              <div class="carousel-item row">
                <div class="col-8 mx-auto text-center my-2">
                  <div>
                    <h5 class="text-brand my-4">Select the length</h5>
                  </div>
                  @foreach(\App\Projects\PianoLit\Tag::lengths()->pluck('name') as $tag)
                  <button class="tag-button tag-final btn btn-light py-3 btn-block"><strong>{{ucfirst($tag)}}</strong></button>
                  @endforeach
                </div>
              </div>
            
          </div>
        </div>

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
<script type="text/javascript">
$('#app-intro .tag-button').on('click', function() {
  $tag = $(this);
  $form = $('#app-intro form');
  $input = $form.find('input[name="search"]');

  $tag.siblings('button').addClass('btn-light').removeClass('btn-default');
  $tag.addClass('btn-default').removeClass('btn-light');

  $tag.closest('.carousel-item').attr('value', $tag.text());

  if ($tag.hasClass('tag-final')) {
    $input.val('');
    
    $('#app-intro .carousel-item').each(function() {
      $input.val($input.val()+$(this).attr('value')+' ');
    });

    $('#app-intro form').submit();
  } else {
    $('#app-intro').carousel('next');
  }
});

</script>
@endsection
