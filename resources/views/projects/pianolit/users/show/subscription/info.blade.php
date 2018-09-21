<div class="col-6">
	<table class="table table-striped table-borderless">
	  <tbody>
		@include('projects/pianolit/users/show/list-item', ['title' => 'Subscription ID', 'value' => $user->subscription->latestPurchase->original_transaction_id])
		@include('projects/pianolit/users/show/list-item', ['title' => 'Bundle ID', 'value' => $user->subscription->bundle_id])
		@include('projects/pianolit/users/show/list-item', ['title' => 'App ID', 'value' => $user->subscription->app_item_id])
		@include('projects/pianolit/users/show/list-item', ['title' => 'Application Version', 'value' => $user->subscription->application_version])
		@include('projects/pianolit/users/show/list-item', ['title' => 'Start Date', 'value' => $user->subscription->receipt_creation_date->toFormattedDateString()])
	  </tbody>
	</table>
</div>
<div class="col-6">
	<div class="accordion" id="subscription-for-{{$user->id}}">
		@foreach($user->subscription->purchases as $receipt)
		<div class="card">
			<div class="card-header" id="headingOne">
				<div class="d-flex justify-content-between cursor-pointer" data-toggle="collapse" data-target="#receipt-{{$loop->iteration}}">
					<strong>Receipt #{{$loop->remaining + 1}}</strong>
					<span>{{carbon($receipt->purchase_date)->toFormattedDateString()}}</span>
				</div>
			</div>

			<div id="receipt-{{$loop->iteration}}" class="collapse" aria-labelledby="headingOne" data-parent="#subscription-for-{{$user->id}}">
				<div class="card-body" style="background-color: rgba(0,0,0,0.01)">
					<table class="table table-hover table-sm table-borderless m-0">
						<tbody>
							@include('projects/pianolit/users/show/list-item', ['title' => 'Plan', 'value' => ucfirst($receipt->product_id)])
							@include('projects/pianolit/users/show/list-item', ['title' => 'Purchase ID', 'value' => $receipt->web_order_line_item_id])
							@include('projects/pianolit/users/show/list-item', ['title' => 'Purchase Date', 'value' => carbon($receipt->purchase_date)->toFormattedDateString()])
							@include('projects/pianolit/users/show/list-item', ['title' => 'Expiration Date', 'value' => carbon($receipt->expires_date)->toFormattedDateString()])
							@include('projects/pianolit/users/show/list-item', ['title' => 'Auto-renew Status', 'value' => $receipt->auto_renew_status ? 'On' : 'Off'])
							@include('projects/pianolit/users/show/list-item', ['title' => 'Price Consent', 'value' => $receipt->auto_renew_status ? 'Agree' : 'Disagree'])
						</tbody>
					</table>
				</div>
			</div>
		</div>
		@endforeach
	</div>
</div>