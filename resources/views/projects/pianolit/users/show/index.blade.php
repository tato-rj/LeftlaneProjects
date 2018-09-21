@extends('projects/pianolit/layouts/app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('projects/pianolit/components/breadcrumb', [
    'title' => 'Users',
    'description' => "$user->first_name's profile"])

    <div class="text-center">
      <a href="{{route('piano-lit.api.user', $user->id)}}" target="_blank" class="link-default"><small>See JSON response</small></a>
    </div>  
   
    @include('projects/pianolit/users/show/basic')

    <ul class="nav nav-tabs mb-2" id="user-menu" role="tablist">
      <li class="nav-item">
        <a class="nav-link active" id="profile-tab" data-toggle="tab" href="#profile" role="tab" aria-controls="profile" aria-selected="true">Profile</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="subscription-tab" data-toggle="tab" href="#subscription" role="tab" aria-controls="subscription" aria-selected="false">Subscription</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="behavior-tab" data-toggle="tab" href="#behavior" role="tab" aria-controls="behavior" aria-selected="false">Behavior</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="manage-tab" data-toggle="tab" href="#manage" role="tab" aria-controls="manage" aria-selected="false">Manage</a>
      </li>
    </ul>

    <div class="tab-content">
      @include('projects/pianolit/users/show/profile')

      @include('projects/pianolit/users/show/subscription/section')

      @include('projects/pianolit/users/show/behavior')

      @include('projects/pianolit/users/show/manage')
  </div>
  </div>
</div>

@include('projects/pianolit/components/modals/delete', ['model' => 'user'])
@include('projects/pianolit/components/modals/trial', ['model' => 'user'])
@include('projects/pianolit/users/favorites')

@endsection

@section('scripts')
<script type="text/javascript">
$('.piece').on('click', function() {
  $piece = $(this);
  $heart = $piece.find('.fa-heart');

  $heart.toggleClass('fas far');

  $.post($piece.attr('data-url'), {'piece_id': $piece.attr('data-id'), 'user_id': $piece.attr('data-user_id')}, 
    function(response){
      console.log(response);
    if(response.passes) {
      // console.log(response);
    } else {
      // console.log('We couldn\'t save your feedback right now.');
    }
  });
});
</script>
@endsection