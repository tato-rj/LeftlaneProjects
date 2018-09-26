<div class="col-8">
	<table class="table table-striped table-borderless">
	  <tbody>
		@include('projects/pianolit/users/show/list-item', 
			['title' => 'Subscription ID', 'value' => $user->subscription->original_transaction_id])
		@include('projects/pianolit/users/show/list-item', 
			['title' => 'Last updated on', 'value' => $user->subscription->updated_at->toDayDateTimeString()])
		@include('projects/pianolit/users/show/list-item', 
			['title' => 'Latest event', 'value' => ucfirst(str_replace('_', ' ', $user->subscription->notification_type))])
		@include('projects/pianolit/users/show/list-item', 
			['title' => 'Environment', 'value' => $user->subscription->environment ?? '-'])
		@include('projects/pianolit/users/show/list-item', 
			['title' => 'Expiration intent', 'value' => $user->subscription->expiration_intent_text ?? '-'])
		@include('projects/pianolit/users/show/list-item', 
			['title' => 'Cancellation date', 'value' => $user->subscription->cancellation_date 
				? \Carbon\Carbon::parse($user->subscription->cancellation_date)->toDayDateTimeString() 
				: '-'])
	  </tbody>
	</table>
</div>
<div class="col-4">
	<div class="card">
		<div class="card-header bg-light" id="headingOne">
			<div>
				<strong><i class="fas fa-file-alt mr-2"></i>Latest Receipt</strong>
			</div>
		</div>

			<div class="card-body">
				<table class="table table-sm table-borderless m-0">
					<tbody>
						@include('projects/pianolit/users/show/list-item', ['title' => 'Plan', 'value' => ucfirst($user->subscription->latest_receipt_info->product_id)])
						@include('projects/pianolit/users/show/list-item', ['title' => 'Purchase ID', 'value' => $user->subscription->latest_receipt_info->web_order_line_item_id])
						@include('projects/pianolit/users/show/list-item', ['title' => 'Purchase Date', 'value' => \Carbon\Carbon::parse($user->subscription->latest_receipt_info->purchase_date)->toFormattedDateString()])
						@include('projects/pianolit/users/show/list-item', ['title' => 'Expiration Date', 'value' => \Carbon\Carbon::parse($user->subscription->latest_receipt_info->expires_date)->toFormattedDateString()])
						@include('projects/pianolit/users/show/list-item', ['title' => 'Auto-renew Status', 'value' => $user->subscription->auto_renew_status ? 'On' : 'Off'])
						@include('projects/pianolit/users/show/list-item', ['title' => 'Price Consent', 'value' => $user->subscription->latest_receipt_info->auto_renew_status ? 'Agree' : 'Disagree'])
					</tbody>
				</table>
			</div>

	</div>

	<div class="text-right">
		<a href="" data-toggle="modal" data-target="#subscription-history" class="link-default"><div class="mb-2">Request receipts history</div></a>
		<a href="{{url()->current()}}?format=json" target="_blank" class="link-default"><div>See JSON response</div></a>
	</div>

</div>