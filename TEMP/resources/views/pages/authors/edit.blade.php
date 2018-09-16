@extends('app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @component('components/breadcrumb')
    Authors
    @slot('description')Edit an author 
    @endslot
  @endcomponent

    <form id="select-form" action="/quickreads/authors/edit/" class="col-lg-6 col-sm-10 col-xs-12 mx-auto my-4">
      <div class="form-group">
        {{-- Stories --}}
        <select class="form-control" onchange="select()">
          <option selected disabled>Choose an author</option>
          @foreach($authors as $select_author)
            <option data-slug="{{$select_author->slug}}" value="{{$select_author->id}}">{{$select_author->name}}</option>
          @endforeach
        </select>        
      </div>
    </form>

    @if(isset($author))

    <form method="POST" action="/quickreads/authors/{{$author->slug}}" class="col-lg-6 col-sm-10 col-xs-12 mx-auto my-5">
      {{csrf_field()}}
      {{method_field('PATCH')}}
      {{-- Name --}}
      <div class="form-group row">
        <label class="col-sm-2 col-form-label text-brand">Name</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" name="name" placeholder="Full name" value="{{ $author->name }}" required>
        </div>
      </div>
      {{-- Life --}}
      <div class="form-group row">
        <label class="col-sm-2 col-form-label text-brand">Life</label>
        <div class="col-sm-10">
          <textarea class="form-control" rows="6" name="life" placeholder="Author's biography" required>{{ $author->life }}</textarea>
        </div>
      </div>
      <div class="row">
        <div class="col-6">
          <div class="form-group row">
            <label class="col-sm-4 col-form-label text-brand">Born in</label>
            {{-- Born in --}}
            <div class="col-sm-8">
              <input type="text" class="form-control" name="born_in" maxlength="6" placeholder="Born in" value="{{ $author->born_in }}" required>
            </div>            
          </div>
        </div>
        <div class="col-6">
          <div class="form-group row">
            <label class="col-sm-4 col-form-label text-brand">Died in</label>
            {{-- Died in --}}
            <div class="col-sm-8">
              <input type="text" class="form-control" name="died_in" maxlength="6" placeholder="Died in" value="{{ $author->died_in }}" required>
            </div>            
          </div>
        </div>
      </div>
      <div class="text-center my-5">
        <button type="submit" class="btn btn-block btn-default">Edit Author</button>
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
