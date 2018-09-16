@extends('app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @component('components/breadcrumb')
    Categories
    @slot('description')Edit a category 
    @endslot
  @endcomponent

    <form id="select-form" action="/quickreads/categories/edit/" class="col-lg-6 col-sm-10 col-xs-12 mx-auto my-4">
      <div class="form-group">
        {{-- Stories --}}
        <select class="form-control" onchange="select()">
          <option selected disabled>Choose the category</option>
          @foreach($categories as $select_category)
            <option data-slug="{{$select_category->slug}}" value="{{$select_category->id}}">{{$select_category->category}}</option>
          @endforeach
        </select>        
      </div>
    </form>

    @if(isset($category))

    <form method="POST" action="/quickreads/categories/{{$category->slug}}" class="col-lg-6 col-sm-10 col-xs-12 mx-auto my-5">
      {{csrf_field()}}
      {{method_field('PATCH')}}
      {{-- Name --}}
      <div class="form-group row">
        <label class="col-sm-2 col-form-label text-brand">Name</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" name="category" placeholder="Category name" value="{{ $category->category }}" required>
        </div>
      </div>
      {{-- Sorting order --}}
      <div class="form-group row">
        <label class="col-sm-2 col-form-label text-brand">Order</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" name="sorting_order" placeholder="Sorting order" value="{{ $category->sorting_order }}" required>
        </div>
      </div>
      <div class="text-center my-5">
        <button type="submit" class="btn btn-block btn-default">Edit Category</button>
      </div>
    </form>
    @endif
  </div>
</div>

@endsection

@section('scripts')
<script type="text/javascript">
function select()
{
  $form = $('#select-form');
  $slug = $('#select-form select option:selected').attr('data-slug');
  window.location = $form.attr('action')+$slug;
}
</script>
@endsection
