@extends('projects/pianolit/layouts/app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('projects/pianolit/components/breadcrumb', [
    'title' => 'Pieces',
    'description' => 'Add a new piece'])
    
    <div class="row my-5 mx-2">
      <form method="POST" action="/piano-lit/pieces" class="col-lg-6 col-sm-10 col-12 mx-auto" autocomplete="off" enctype="multipart/form-data">
        {{csrf_field()}}
        <div id="name-validation-notice" style="display: none;">
          <p class="text-danger text-center"><small><i class="fas fa-ban mr-1"></i>Ops, the <strong><span></span></strong> already exists!</small></p>
        </div>
        {{-- Name --}}
        <div class="form-group form-row">
          <div class="col">
            <input type="text" class="validate-name form-control {{$errors->has('name') ? 'is-invalid' : ''}}" name="name" placeholder="Piece name" value="{{ old('name') }}" >
            @include('projects/pianolit/components/feedback', ['field' => 'name'])
          </div>
          <div class="col">
            <input type="text" class="validate-name form-control" name="nickname" placeholder="Nickname" value="{{ old('nickname') }}" >
          </div>
        </div>
        <div class="form-group form-row">
          <div class="col">
            <input type="text" class="validate-name form-control" name="collection_name" placeholder="Collection name" value="{{ old('collection_name') }}">
          </div>
          <div class="col">
            <div class="input-group">
              <div class="input-group-prepend">
                <select class="form-control rounded-left" style="border-radius: 0" name="catalogue_name" >
                  <option selected disabled>Catalogue</option>
                  @foreach(\App\Projects\PianoLit\Piece::catalogues() as $catalogue)
                  <option value="{{$catalogue}}" {{ old('catalogue_name') == $catalogue ? 'selected' : ''}}>{{$catalogue}}</option>
                  @endforeach
                </select>
              </div>
              <input type="text" class="validate-name form-control" name="catalogue_number" placeholder="Number" value="{{ old('catalogue_number') }}">
            </div>
          </div>
        </div>
        {{-- Catalogue and number --}}
        <div class="form-row form-group">
          <div class="col">
            <div class="input-group">
              <div class="input-group-prepend">
                <div class="input-group-text">No.</div>
              </div>
              <input type="text" class="validate-name form-control" name="collection_number" placeholder="Number" value="{{ old('collection_number') }}">
            </div>
          </div>
          <div class="col">
            <div class="input-group">
              <div class="input-group-prepend">
                <div class="input-group-text">Mov.</div>
              </div>
              <input type="text" class="validate-name form-control" name="movement_number" placeholder="Number" value="{{ old('movement_number') }}">
            </div>
          </div>
        </div>
        {{-- Key and Composer --}}
        <div class="form-row form-group">
          <div class="col">
            <select class="form-control {{$errors->has('composer_id') ? 'is-invalid' : ''}}" name="composer_id">
              <option selected disabled>Composer</option>
              @foreach($composers as $composer)
              <option value="{{$composer->id}}" {{ old('composer_id') == $composer->id ? 'selected' : ''}}>{{$composer->short_name}}</option>
              @endforeach
            </select>
            @include('projects/pianolit/components/feedback', ['field' => 'composer_id'])
          </div>
          <div class="col">
            <select class="form-control {{$errors->has('key') ? 'is-invalid' : ''}}" name="key" >
              <option selected disabled>Key</option>
              <optgroup label="Tonal">
                @foreach(\App\Projects\PianoLit\Piece::keys() as $key)
                <option value="{{$key}}" {{ old('key') == $key ? 'selected' : ''}}>{{$key}}</option>
                @endforeach
              </optgroup>
              <optgroup label="Non-tonal">
                <option value="Modal" {{ old('key') == 'Modal' ? 'selected' : ''}}>Modal</option>
                <option value="Serial" {{ old('key') == 'Serial' ? 'selected' : ''}}>Serial</option>
                <option value="Chromatic" {{ old('key') == 'Chromatic' ? 'selected' : ''}}>Chromatic</option>
              </optgroup>
            </select>
            @include('projects/pianolit/components/feedback', ['field' => 'key'])
          </div>
        </div>
        {{-- Period, Length and Level --}}
        <div class="form-row form-group">
          <div class="col">
            <select class="form-control {{$errors->has('period') ? 'is-invalid' : ''}}" name="period[]" >
              <option selected disabled>Period</option>
              @foreach(\App\Projects\PianoLit\Tag::periods()->get() as $period)
              <option value="{{$period->id}}" {{ old('period') == $period->id ? 'selected' : ''}}>{{ucfirst($period->name)}}</option>
              @endforeach
            </select>
            @include('projects/pianolit/components/feedback', ['field' => 'period'])
          </div>
          <div class="col">
            <select class="form-control {{$errors->has('length') ? 'is-invalid' : ''}}" name="length[]" >
              <option selected disabled>Length</option>
              @foreach(\App\Projects\PianoLit\Tag::lengths()->get() as $length)
              <option value="{{$length->id}}" {{ old('length') == $length->id ? 'selected' : ''}}>{{ucfirst($length->name)}}</option>
              @endforeach
            </select>
            @include('projects/pianolit/components/feedback', ['field' => 'length'])
          </div>
          <div class="col">
            <select class="form-control {{$errors->has('level') ? 'is-invalid' : ''}}" name="level[]" >
              <option selected disabled>Level</option>
              @foreach(\App\Projects\PianoLit\Tag::levels()->get() as $level)
              <option value="{{$level->id}}" {{ old('level') == $level->id ? 'selected' : ''}}>{{ucfirst($level->name)}}</option>
              @endforeach
            </select>
            @include('projects/pianolit/components/feedback', ['field' => 'level'])
          </div>
        </div>
        {{-- Did you know? --}}
        <div class="form-group">
          <textarea class="form-control" rows="3" name="curiosity" placeholder="Enter a curiosity here">{{old('score_editor')}}</textarea>
        </div>
        @manager
        {{-- Score Info --}}
        <hr class="my-4">
        <div class="form-group form-row">
          <div class="col">
            <input type="text" class="form-control" name="score_editor" placeholder="Score editor" value="{{old('score_editor')}}">
          </div>
          <div class="col">
            <input type="text" class="form-control" name="score_copyright" placeholder="Score copyright" value="{{old('score_copyright')}}">
          </div>
        </div>
        <div class="form-group">
          <input type="text" class="form-control" name="score_publisher" placeholder="Score publisher" value="{{ old('score_publisher') }}">
          @include('projects/pianolit/components/lookup')
        </div>
        {{-- Files --}}
        <div class="form-row form-group">
          <div class="col input-group">
            <div class="input-group-prepend">
              <div class="input-group-text"><i class="fas fa-microphone"></i></div>
            </div>
            <div class="custom-file">
              <input type="file" class="custom-file-input" name="audio_path" id="customFile">
              <label class="custom-file-label truncate" for="customFile">Both hands</label>
            </div>
          </div>
          <div class="col input-group">
            <div class="input-group-prepend">
              <div class="input-group-text"><i class="fas fa-file-alt"></i></div>
            </div>
            <div class="custom-file">
              <input type="file" class="custom-file-input" name="score_path" id="customFile">
              <label class="custom-file-label truncate" for="customFile">Score</label>
            </div>
          </div>
        </div>
        <div class="form-row form-group">
          <div class="col input-group">
            <div class="input-group-prepend">
              <div class="input-group-text"><i class="fas fa-hand-paper" style="transform: scaleX(-1)"></i></div>
            </div>
            <div class="custom-file">
              <input type="file" class="custom-file-input" name="audio_path_lh" id="customFile">
              <label class="custom-file-label truncate" for="customFile">Left hand audio</label>
            </div>
          </div>
          <div class="col input-group">
            <div class="input-group-prepend">
              <div class="input-group-text"><i class="fas fa-hand-paper"></i></div>
            </div>
            <div class="custom-file">
              <input type="file" class="custom-file-input" name="audio_path_rh" id="customFile">
              <label class="custom-file-label truncate" for="customFile">Right hand audio</label>
            </div>
          </div>
        </div>
{{--         <div class="form-group">
          <select class="form-control {{$errors->has('performer_id') ? 'is-invalid' : ''}}" name="performer_id" >
            <option selected disabled>Performer</option>
            <option>PianoLIT</option>
          </select>
          @include('projects/pianolit/components/feedback', ['field' => 'key'])
        </div> --}}
        @endmanager
        {{-- Tags --}}
        <hr class="my-4">
        <div class="rounded bg-light px-3 py-2 mb-3">
          <p class="text-brand border-bottom pb-1 mb-1"><strong>TAGS</strong></p>
          <div class="d-flex flex-wrap">
            @foreach($types as $type => $tags)
            <label class="p-2 mb-1 text-center w-100"><strong>{{ucfirst($type)}}</strong></label>
              @foreach($tags as $tag)
              <div class="custom-control custom-checkbox mx-2 mb-2">
                <input type="checkbox" class="custom-control-input" name="tags[]" value="{{$tag->id}}" id="{{$tag->name}}">
                <label class="custom-control-label" for="{{$tag->name}}">{{$tag->name}}</label>
              </div>
              @endforeach
            @endforeach
          </div>
          <div class="mb-1 mt-4 ml-2 text-muted">
            <small>Special tags are: {{\App\Projects\PianoLit\Tag::special()->get()->implode('name', ', ')}}</small>
          </div>
        </div>
        @manager
        {{-- iTunes --}}
        @include('projects/pianolit/components/itunes/layout')
        {{-- Youtube --}}
        @include('projects/pianolit/components/youtube/layout', ['subject' => 'YOUTUBE'])
        @endmanager
        {{-- Tips --}}
        @include('projects/pianolit/components/tips/layout', ['subject' => 'TIPS'])

        <div class="text-center my-5">
          <button type="submit" class="btn btn-block btn-default">Add piece</button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script type="text/javascript" src="{{asset('js/vendor/jquery.ba-throttle-debounce.min.js')}}"></script>
<script type="text/javascript" src="{{asset('js/lookup.js')}}"></script>
<script type="text/javascript">
function lastWord(words) {
  var n = words.split(" ");
  return n[n.length - 1];
}
$('select[name="level[]"], select[name="composer_id"]').on('change', function() {
  let level = $('select[name="level[]"] option:selected').text();
  let composer = $('select[name="composer_id"] option:selected').text();
  let search = level + ' ' + lastWord(composer);
  $.get('/piano-lit/pieces/suggest-tips?global=true&search='+search, function(data, status){
      $('#suggestions').html(data);
  });
}); 
</script>

<script type="text/javascript">

new Lukup({
  url: app.url+'/piano-lit/pieces/single-lookup',
  field: 'score_publisher',
  autofill: ['score_publisher']
}).enable();

new Lukup({
  url: app.url+'/piano-lit/pieces/single-lookup',
  field: 'score_editor',
  autofill: ['score_editor']
}).enable();

new Lukup({
  url: app.url+'/piano-lit/pieces/single-lookup',
  field: 'score_copyright',
  autofill: ['score_copyright']
}).enable();

new Lukup({
  url: app.url+'/piano-lit/pieces/single-lookup',
  field: 'nickname',
  autofill: ['nickname']
}).enable();

new Lukup({
  url: app.url+'/piano-lit/pieces/single-lookup',
  field: 'name',
  autofill: ['name']
}).enable();

new Lukup({
  url: app.url+'/piano-lit/pieces/multi-lookup',
  field: 'collection_name',
  autofill: ['collection_name', 'nickname', 'catalogue_name', 'catalogue_number', 'composer_id']
}).enable();

</script>

<script type="text/javascript">
$('input.validate-name').on('focus', function() {
  $.post(app.url+'/piano-lit/pieces/validate-name', {
    name: $('input[name="name"]').val(),
    collection_number: $('input[name="collection_number"]').val(),
    catalogue_number: $('input[name="catalogue_number"]').val()
  }, function(data, status) {
    if (data != '') {
      $('#name-validation-notice span').text(data);
      $('#name-validation-notice').show();
      $('input, select, textarea').removeClass('border-warning');
    } else {
      $('#name-validation-notice').hide();
    }
  });
});
</script>

<script type="text/javascript">
/////////////////
// ADD NEW TIP //
/////////////////
$('a.add-new-field').on('click', function() {
  $button = $(this);
  $type = $button.attr('data-type');
  $clone = $button.siblings('.original-type').clone();

  if ($type == 'itunes') {
    number = $('.itunes-form:not(.original-type)').length;
    inputs = $clone.find('input');
    $(inputs[0]).attr('name',  'itunes['+number+'][album]');
    $(inputs[1]).attr('name',  'itunes['+number+'][artist]');
    $(inputs[2]).attr('name',  'itunes['+number+'][link]');
    $clone.removeClass('original-type').insertBefore($button).show();

  } else if ($type == 'youtube') {
    $clone.find('input').attr('name',  'youtube[]');
    $clone.removeClass('original-type').insertBefore($button).addClass('d-flex');

  } else {
    $clone.find('input').attr('name',  'tips[]');
    $clone.removeClass('original-type').insertBefore($button).addClass('d-flex');
  }
});

////////////////
// REMOVE TIP //
////////////////
$(document).on('click', 'a.remove-field', function() {
  $(this).parent().remove();
});

/////////////////////////////
// HIGHLIGHT UPLOADED FILE //
/////////////////////////////
$('input[type="file"]').on('change', function(e) {
  $input = $(this);
  $label = $input.siblings('label');
  filename = e.target.files[0].name;

  $icon = $input.parent().siblings().find('> div').addClass('text-success');
  $label.text(filename);
});
</script>
@endsection
