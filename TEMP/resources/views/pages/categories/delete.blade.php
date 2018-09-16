@extends('app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @component('components/breadcrumb')
    Categories
    @slot('description')Remove a category 
    @endslot
  @endcomponent

    <div class="col-lg-8 col-sm-10 col-xs-12 mx-auto my-4">
      <form id="select-form">
        <select class="form-control" onchange="select()">
          <option selected disabled>Choose a category</option>
          @foreach($categories as $select_category)
            <option data-slug="{{$select_category->slug}}" value="{{$select_category->id}}">{{$select_category->category}}</option>
          @endforeach
        </select>      
      </form>  
    </div>
  </div>
</div>

@component('components/modals/delete')
categories
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
