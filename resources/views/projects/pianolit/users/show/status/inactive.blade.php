<div class="col-12 p-3">
	@if($user->subscription->cancellation_date)

	<div class="p-3 alert alert-danger" role="alert"><i class="fas fa-ban mr-2"></i>
		{{$user->first_name}} has cancelled the subscription on <strong>{{$user->subscription->cancellation_date->toFormattedDateString()}}</strong>.
	</div>

	@else

	<div class="p-3 alert alert-danger" role="alert"><i class="fas fa-ban mr-2"></i>
		{{$user->first_name}} subscribed on {{$user->subscription->receipt_creation_date->toFormattedDateString()}}, 
		but the subscription expired on <strong>{{$user->subscription->renews_at->toFormattedDateString()}}</strong>.
	</div>	

	@endif
</div>

@include('projects/pianolit/users/show/subscription/info')