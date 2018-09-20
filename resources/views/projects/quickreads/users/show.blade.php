  <div class="card">
    <div class="card-header cursor-pointer bg-white d-flex justify-content-between align-items-center" id="headingOne" data-toggle="collapse" data-target="#user-{{$user->id}}" aria-expanded="true" aria-controls="user-{{$user->id}}">
      <p class="mb-0">
          <strong>{{$user->fullName}}</strong><span class="ml-2 badge badge-light text-muted">{{$user->stories()->count()}} {{ str_plural('story', $user->stories()->count()) }}</span>
      </p>
      <p class="m-0 text-muted">joined on {{$user->created_at->toFormattedDateString()}}</p>
    </div>

    <div id="user-{{$user->id}}" class="collapse" aria-labelledby="headingOne" data-parent="#accordion">
      <div class="card-body">
        <div class="row">
          <div class="col-lg-3 col-md-4 col-sm-4 col-12 mb-2">
            <img class="rounded mb-2" style="width: 80px" src="{{$user->profilePicture}}">
            <div>
              <p class="m-0" style="color: #2093a5"><small>Email</small></p>
              <p class="m-0">{{$user->email}}</p>
            </div>
            <div>
              <p class="m-0" style="color: #2093a5"><small>Country</small></p>
              <p class="m-0">{{locale_get_display_region($user->locale)}}</p>
            </div>
            <div>
              <p class="m-0" style="color: #2093a5"><small>Gender</small></p>
              <p class="m-0">{{ucfirst($user->gender)}}</p>
            </div>
            <div class="mt-4">
              <button class="delete-user btn btn-danger btn-sm" data-slug="{{$user->slug}}" data-name="{{$user->fullName}}">Delete user</button>
            </div>
          </div>

        	<div class="col-lg-9 col-md-8 col-sm-8 col-12">
        		@foreach($user->stories as $story)
              @include('projects/quickreads/users/stories')
        		@endforeach
        	</div>

        </div>
      </div>
    </div>
  </div>