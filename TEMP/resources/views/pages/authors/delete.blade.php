@extends('app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @component('components/breadcrumb')
    Authors
    @slot('description')Remove an author 
    @endslot
  @endcomponent

    <div class="col-lg-8 col-sm-10 col-xs-12 mx-auto my-4">
      <form id="select-form">
        <select class="form-control" onchange="select()">
          <option selected disabled>Choose the author</option>
          @foreach($authors as $select_story)
            <option data-slug="{{$select_story->slug}}" value="{{$select_story->id}}">{{$select_story->name}}</option>
          @endforeach
        </select>     
      </form>   
    </div>
  </div>
</div>

@component('components/modals/delete')
authors
@endcomponent
@endsection

@section('scripts')
<script type="text/javascript">
function select()
{
  $name = $('#select-form select option:selected').text();
  $slug = $('#select-form select option:selected').attr('data-slug');
  $modal = $('#delete-modal');
  $modal.find('input[name="slug"]').val($slug);
  $modal.find('#name').text($name);
  $modal.find('form').attr('action', $modal.find('form').attr('action')+$slug);
  $modal.modal('show');
}
</script>
@endsection
