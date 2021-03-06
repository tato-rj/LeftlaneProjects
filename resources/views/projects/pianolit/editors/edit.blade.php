@extends('projects/pianolit/layouts/app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('projects/pianolit/components/breadcrumb', [
    'title' => 'Editors',
    'description' => "Edit {$editor->name}'s profile"])

    <div class="row my-5 mx-2">
      <form id="edit-form" method="POST" action="{{route('piano-lit.editors.update', $editor->id)}}" class="col-lg-6 col-sm-10 col-12 mx-auto">
        {{csrf_field()}}
        {{method_field('PATCH')}}
        {{-- Name --}}
        <div class="form-group row">
          <label class="col-sm-2 col-form-label text-brand">Name</label>
          <div class="col-sm-10">
            <input type="text" class="form-control" name="name" placeholder="Full name" value="{{ $editor->name }}" required>
          </div>
        </div>
        {{-- Email --}}
        <div class="form-group row">
          <label class="col-sm-2 col-form-label text-brand">E-mail</label>
          <div class="col-sm-10">
            <input type="text" class="form-control" name="email" placeholder="E-mail" value="{{ $editor->email }}" required>
          </div>
        </div>

        {{-- Password --}}
        <div class="form-group row">
          <label class="col-sm-2 col-form-label text-brand">Password</label>
          <div class="col-sm-10">
            <button type="button" class="btn btn-xs btn-light" data-toggle="modal" data-target="#password-modal">
              Change password
            </button>
          </div>
        </div>        

        <div class="text-center mt-5">
          <button type="submit" class="btn btn-block btn-default">Save changes</button>
        </div>
      </form>
      
      <div class="col-lg-6 col-sm-10 col-12 mx-auto">
        <p class="text-muted"><strong>{{$editor->name}} created {{$pieces->count()}} {{str_plural('piece', $pieces->count())}}</strong></p>
        @if($pieces->count() > 0)
        <ul class="list-style-none pl-2">
          @foreach($pieces as $piece)
          <li class="mb-2">
            <a href="{{route('piano-lit.pieces.edit', $piece)}}">
              <i class="fas fa-caret-right mr-2"></i>{{$piece->long_name}}
            </a>
          </li>
          @endforeach
        </ul>
        @endif
      </div>
    </div>
  </div>
</div>

@include('projects/pianolit/components/modals/password')
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
