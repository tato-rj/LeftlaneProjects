@extends('app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @component('components/breadcrumb')
    Users
    @slot('description')Data from the users
    @endslot
  @endcomponent
<p class="m-4">The total number of users is <strong>{{$users->count()}}</strong></p>
<div id="accordion">
	@foreach($users as $user)

    @include('pages/users/show')

  @endforeach
</div>

  </div>
</div>

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
@endsection
