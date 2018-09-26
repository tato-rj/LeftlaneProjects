<div class="d-flex justify-content-between mt-4 mb-5 mx-3">
  <div class="d-flex">
    <div class="mr-5">
      <img src="https://api.adorable.io/avatars/236/{{$user->email}}" class="rounded-circle" style="width: 160px">
    </div>

    <div class="mr-5">
      <div>
        <label class="text-brand m-0"><small>Name</small></label>
        <p>{{$user->full_name}}</p>
      </div>
      <div>
        <label class="text-brand m-0"><small>E-mail</small></label>
        <p>{{$user->email}}</p>
      </div>
    </div>
    
    <div class="mr-5">
      <div>
        <label class="text-brand m-0"><small>Language</small></label>
        <p>{{Locale::getDisplayName($user->locale)}}</p>
      </div>
      <div>
        <label class="text-brand m-0"><small>Status</small></label>
        <p>
          @if($user->status() == 'trial')
            <span class="text-warning">Trial ends in {{$user->trial_ends_at->diffForHumans()}} ({{$user->trial_ends_at->toFormattedDateString()}})</span>
          @elseif($user->status() == 'expired')
            <span class="text-muted">Trial expired</span>
          @elseif($user->status() == 'active')
            <span class="text-success">Active
              @if($user->subscription->expires_at)
              <strong>(set to expire on {{$user->subscription->expires_at->toFormattedDateString()}})</strong>
              @endif
            </span>
          @elseif($user->status() == 'pending')
            <span class="text-warning">Active, but pending Apple's confirmation</span>
          @elseif($user->status() == 'inactive')
            @if($user->subscription->cancellation_date)
            <span class="text-danger">Subscription was cancelled</span>
            @else
            <span class="text-danger">Subscription expired</span>
            @endif
          @endif
        </p>
      </div>
    </div>
  </div>
</div>
