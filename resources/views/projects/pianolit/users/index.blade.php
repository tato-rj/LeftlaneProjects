@extends('projects/pianolit/layouts/app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('projects/pianolit/components/breadcrumb', [
    'title' => 'Users',
    'description' => 'View detailed information about the users'])

    <div class="row my-3">
      <div class="col-12 text-center">
        <p class="text-center"><small>Showing {{$users->count()}} of {{$users->total()}}</small></p>
      </div>
      @foreach($users as $user)
      <div class="col-12 mb-2">
        <div class="d-flex bg-light rounded-right text-muted">
          
            @if($user->is_active)
            <div class="px-3 py-1 bg-success rounded-left">
              <span class="text-white">Active</span>
            </div>
            @else
            <div class="px-3 py-1 bg-danger rounded-left">
              <span class="text-white">Inactive</span>
            </div>
            @endif
          <div class="px-3 py-1 border-bottom border-top" style="flex-grow: 2">
            <span>
              <strong>{{$user->full_name}}</strong> | <small><i>signed up on {{$user->created_at->toFormattedDateString()}}</i></small>
            </span>
          </div>
          <div class="text-right text-brand px-3 border-top border-right border-bottom rounded-right py-1 d-flex align-items-center">
            <a href="/piano-lit/users/{{$user->id}}"><i class="fas fa-info-circle mr-2"></i>view details</a>
          </div>
        </div>
      </div>
      @endforeach
    </div>

    {{-- PAGINATION --}}
    <div class="row mb-3">
          <div class="d-flex align-items-center w-100 justify-content-center my-4">
        {{ $users->links() }}    
        </div>
    </div>

  </div>
</div>

@if($errors->any())
  @include('projects/pianolit/components/alerts/error')
@endif

@endsection

@section('scripts')

@endsection