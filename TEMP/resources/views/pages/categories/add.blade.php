@extends('app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @component('components/breadcrumb')
    Categories
    @slot('description')Add a new category 
    @endslot
  @endcomponent
    <form method="POST" action="/quickreads/categories" class="col-lg-6 col-sm-10 col-xs-12 mx-auto my-4">
      {{csrf_field()}}
      {{-- Name --}}
      <div class="form-group">
        <input type="text" class="form-control" name="category" placeholder="Category name" value="{{ old('category') }}" required>
      </div>
      <div class="text-center">
        <button type="submit" class="btn btn-block btn-default">Add Category</button>
      </div>
      
    </form>   
  </div>
</div>

@endsection