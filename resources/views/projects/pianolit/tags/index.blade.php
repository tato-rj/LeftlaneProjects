@extends('projects/pianolit/layouts/app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('projects/pianolit/components/breadcrumb', [
    'title' => 'Tags',
    'description' => 'Manage the tags'])
    
    <div class="row mb-3">
      <div class="col-12">
        <form method="POST" action="/piano-lit/tags" class="form-inline">
          {{csrf_field()}}
          <input type="text" name="name" placeholder="Create a new tag here" class="form-control mr-2">
          <button type="submit" class="btn btn-default">Save</button>
        </form>
        @include('projects/pianolit/components/feedback', ['field' => 'name'])
      </div>
    </div>

    <div class="row my-3">
      <div class="col-12 text-center">
        {{-- <p class="text-center"><small>Showing {{$tags->count()}} of {{$tags->total()}}</small></p> --}}
      </div>
      <div class="col-12">
        @foreach($types as $type => $tags)
          <label class="p-2 border-bottom w-100"><strong>{{ucfirst($type)}}</strong></label>
          <div class="d-flex flex-wrap mb-2">
          @foreach($tags as $tag)
            @include('projects/pianolit/tags/tag')
          @endforeach
          </div>
        @endforeach
      </div>
      <div class="col-12 mt-4 ml-2">
        <p class="text-muted"><small>Ps: Tags with a <i class="fas fa-star text-warning fa-xs"></i> are the ones showing in the tour screen on the app.</small></p>
      </div>
    </div>

  </div>
</div>

@include('projects/pianolit/components/modals/tag')

@endsection

@section('scripts')
<script type="text/javascript">
$('.tag').on('click', function (e) {
  $tag = $(this);
  name = $tag.attr('data-name');
  type = $tag.attr('data-type');
  creator = $tag.attr('data-creator');
  edit_url = $tag.attr('data-edit-url');
  delete_url = $tag.attr('data-delete-url');

  $('#tag-modal').find('form#delete-tag').attr('action', delete_url);
  $('#tag-modal').find('form#edit-tag').attr('action', edit_url);
  $('#tag-modal').find('input#name').val(name);
  $('#tag-modal').find('select[name="type"] option[value="'+type+'"]').prop('selected', true);
  $('#tag-modal').find('#creator').text(creator);
})
</script>
@endsection