@extends('projects/quickreads/layouts/app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @component('projects/quickreads/components/breadcrumb')
    Users
    @slot('description')Data from the users
    @endslot
  @endcomponent
<p class="m-4">The total number of users is <strong>{{$users->count()}}</strong></p>
<div id="accordion">
	@foreach($users as $user)

    @include('projects/quickreads/users/show')

  @endforeach
</div>

  </div>
</div>

@component('projects/quickreads/components/modals/delete')
users
@endcomponent
@endsection

@section('scripts')
<script type="text/javascript">
$('.fb-picture .image').on('mouseover', function() {
	$(this).parent().find('img').fadeIn('fast');
});
$('.fb-picture .image').on('mouseleave', function() {
	$(this).parent().find('img').fadeOut('fast');
});
</script>
<script type="text/javascript">
$('.delete-user').on('click', function() {
  $slug = $(this).attr('data-slug');
  $name = $(this).attr('data-name');
  
  $modal = $('#delete-modal');
  $modal.find('input[name="slug"]').val($slug);
  $modal.find('#name').text($name);
  $modal.find('form').attr('action', $modal.find('form').attr('action')+$slug);
  $modal.modal('show');
});
</script>
@endsection
