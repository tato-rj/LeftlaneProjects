<div class="col-12 p-3">
	<div class="p-3 alert alert-danger" role="alert"><i class="fas fa-ban mr-2"></i>{{$user->first_name}} subscribed on {{$user->subscription->receipt_creation_date->toFormattedDateString()}}, but the subscription expired on <strong>{{$user->subscription->renews_at->toFormattedDateString()}}</strong>.</div>
</div>

@include('projects/pianolit/users/show/subscription/info')