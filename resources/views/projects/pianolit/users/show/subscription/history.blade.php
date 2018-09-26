<table class="table table-striped table-borderless">
  <tbody>
	@include('projects/pianolit/users/show/list-item', ['title' => 'Subscription ID', 'value' => $history['in_app'][0]['original_transaction_id']])
	@include('projects/pianolit/users/show/list-item', ['title' => 'Created on', 'value' => \Carbon\Carbon::parse($history['receipt_creation_date'])->toDayDateTimeString()])
	@include('projects/pianolit/users/show/list-item', ['title' => 'Receipts type', 'value' => $history['receipt_type']])
	@include('projects/pianolit/users/show/list-item', ['title' => 'Application version', 'value' => $history['application_version']])
  </tbody>
</table>

<div class="accordion mb-4" id="subscription-history-receipts">
	@foreach($history['in_app'] as $receipt)
	<div class="card">
		<div class="card-header bg-pastel" id="receipt-{{$loop->iteration}}">
			<div class="d-flex justify-content-between cursor-pointer" data-toggle="collapse" data-target="#receipt-history-{{$loop->iteration}}">
				<strong><i class="fas fa-file-alt mr-2"></i>Receipt #{{$loop->remaining + 1}}</strong>
			</div>
		</div>

		<div id="receipt-history-{{$loop->iteration}}" class="collapse" aria-labelledby="receipt-{{$loop->iteration}}" data-parent="#subscription-history-receipts">
			<div class="card-body" style="background-color: rgba(0,0,0,0.01)">
				<table class="table table-hover table-sm table-borderless m-0">
					<tbody>
						@include('projects/pianolit/users/show/list-item', ['title' => 'Plan', 'value' => ucfirst($receipt['product_id'])])
						{{-- @include('projects/pianolit/users/show/list-item', ['title' => 'Purchase ID', 'value' => $user->subscription->latest_receipt_info->web_order_line_item_id])
						@include('projects/pianolit/users/show/list-item', ['title' => 'Purchase Date', 'value' => \Carbon\Carbon::parse($user->subscription->latest_receipt_info->purchase_date)->toFormattedDateString()])
						@include('projects/pianolit/users/show/list-item', ['title' => 'Expiration Date', 'value' => \Carbon\Carbon::parse($user->subscription->latest_receipt_info->expires_date)->toFormattedDateString()])
						@include('projects/pianolit/users/show/list-item', ['title' => 'Auto-renew Status', 'value' => $user->subscription->auto_renew_status ? 'On' : 'Off'])
						@include('projects/pianolit/users/show/list-item', ['title' => 'Price Consent', 'value' => $user->subscription->latest_receipt_info->auto_renew_status ? 'Agree' : 'Disagree']) --}}
					</tbody>
				</table>
			</div>
		</div>
	</div>
	@endforeach
</div>