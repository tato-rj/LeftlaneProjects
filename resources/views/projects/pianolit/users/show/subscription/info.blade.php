<div class="col-6">
	<table class="table table-striped table-borderless">
	  <tbody>
		@include('projects/pianolit/users/show/list-item', 
			['title' => 'Subscription ID', 'value' => $user->subscription->latest_receipt_info->original_transaction_id])
		@include('projects/pianolit/users/show/list-item', 
			['title' => 'Plan', 'value' => ucfirst($user->subscription->latest_receipt_info->product_id)])
		@include('projects/pianolit/users/show/list-item',
			['title' => 'Start date', 'value' => $user->subscription->created_at->toDayDateTimeString()])
		@include('projects/pianolit/users/show/list-item',
			['title' => 'Next due date', 'value' => $user->subscription->renews_at ? $user->subscription->renews_at->toDayDateTimeString() : '-'])
	  </tbody>
	</table>
</div>
<div class="col-6">

	<a href="" data-toggle="modal" data-target="#subscription-history" class="link-default">
		<div class="mb-2">Request receipts history</div>
	</a>
	<a href="{{url()->current()}}?format=json" target="_blank" class="link-default"><div>See JSON response</div></a>

</div>