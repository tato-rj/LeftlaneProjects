@extends('app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @component('components/breadcrumb')
    Authors
    @slot('description')Add a new author 
    @endslot
  @endcomponent
    <form method="POST" action="/quickreads/authors" class="col-lg-6 col-sm-10 col-xs-12 mx-auto my-4">
      {{csrf_field()}}
      {{-- Name --}}
      <div class="form-group">
        <input type="text" class="form-control" name="name" placeholder="Full name" value="{{ old('name') }}" required>
      </div>
      {{-- Life --}}
      <div class="form-group">
        <textarea class="form-control" rows="4" name="life" placeholder="Life's summary" required>{{ old('life') }}</textarea>
      </div>
      {{-- Dates --}}
      <div class="form-row">
        <div class="col-auto">
          <input class="form-control" type="text" size="6" maxlength="5" name="born_in" placeholder="Year born" value="{{ old('born_in') }}">
        </div>
        <div class="col-auto">
          <input class="form-control" type="text" size="6" maxlength="5" name="died_in" placeholder="Year died" value="{{ old('died_in') }}">
        </div>
      </div>

      <div class="text-center my-5">
        <button type="submit" class="btn btn-block btn-default">Add Author</button>
      </div>
      
    </form>   
  </div>
</div>

@endsection