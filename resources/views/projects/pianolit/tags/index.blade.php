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
        <p class="text-center"><small>Showing {{$tags->count()}} of {{$tags->total()}}</small></p>
      </div>
      <div class="col-12 d-flex flex-wrap">
        @foreach($tags as $tag)
          @include('projects/pianolit/tags/tag')
        @endforeach
      </div>
      <div class="col-12 mt-4 ml-2">
        <p class="text-muted"><small>Ps: Tags with a <i class="fas fa-star text-warning fa-xs"></i> are the ones showing in the tour screen on the app.</small></p>
      </div>
    </div>

    {{-- PAGINATION --}}
    <div class="row mb-3">
          <div class="d-flex align-items-center w-100 justify-content-center my-4">
        {{ $tags->links() }}    
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
  creator = $tag.attr('data-creator');
  edit_url = $tag.attr('data-edit-url');
  delete_url = $tag.attr('data-delete-url');

  $('#tag-modal').find('form#delete-tag').attr('action', delete_url);
  $('#tag-modal').find('form#edit-tag').attr('action', edit_url);
  $('#tag-modal').find('input#name').val(name);
  $('#tag-modal').find('#creator').text(creator);
})
</script>
@endsection