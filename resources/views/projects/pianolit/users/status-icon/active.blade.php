@if($user->subscription->renews_at)
<div class="text-success" title="{{$user->first_name}}'subscription will auto-renew on {{$user->subscription->renews_at->toFormattedDateString()}}">
	<i class="fas fa-check-circle"></i>
</div>
@elseif($user->subscription->expires_at)
<div class="text-danger" title="{{$user->first_name}}'subscription will expire on {{$user->subscription->expires_at->toFormattedDateString()}}">
	<i class="fas fa-check-circle"></i>
</div>
@endif