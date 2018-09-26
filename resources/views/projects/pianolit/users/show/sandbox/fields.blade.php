<input type="hidden" name="notification_type" value="{{$type}}">
<input type="hidden" name="original_transaction_id" value="{{$user->subscription->original_transaction_id}}">
<input type="hidden" name="environment" value="Sandbox">
<input type="hidden" name="latest_receipt" value="{{$user->subscription->latest_receipt}}">
<input type="hidden" name="latest_receipt_info" value="{{json_encode($user->subscription->latest_receipt_info)}}">
<input type="hidden" name="auto_renew_adam_id" value="appleId">
<input type="hidden" name="auto_renew_product_id" value="productId">


@if($type == 'DID_CHANGE_RENEWAL_PREF')
<input type="hidden" name="auto_renew_status" value="{{$user->subscription->auto_renew_status ? 0 : 1}}">
<input type="hidden" name="expiration_intent" value="{{$user->subscription->auto_renew_status ? rand(1,5) : 0}}">
@else
<input type="hidden" name="auto_renew_status" value="{{$user->subscription->auto_renew_status ?? 1}}">
<input type="hidden" name="expiration_intent" value="{{$user->subscription->expiration_intent ?? 0}}">
@endif

@if($type == 'CANCEL')
<input type="hidden" name="cancellation_date" value="{{\Carbon\Carbon::now()->format('Y-m-d h:i:s e')}}">
@endif
