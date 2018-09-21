<div class="col-12 p-3">
	<div class="p-3 alert alert-success" role="alert"><i class="fas fa-check-circle mr-2"></i>{{$user->first_name}}'s susbcribed on {{$user->subscription->receipt_creation_date->toFormattedDateString()}}! The next auto-renewal date is in {{$user->subscription->renews_at->diffForHumans()}} on <strong>{{$user->subscription->renews_at->toFormattedDateString()}}</strong>.</div>
</div>

@include('projects/pianolit/users/show/subscription/info')