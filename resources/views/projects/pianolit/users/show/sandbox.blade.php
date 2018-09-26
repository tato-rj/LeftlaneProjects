<div class="tab-pane fade {{request('section') == 'sandbox' ? 'show active' : null}} m-3" id="sandbox">
  <div class="row">
    @if(! $user->subscription()->exists())

    <div class="col-lg-4 col-md-4 col-sm-8 col-8 p-3">
      <form method="POST" action="{{route('piano-lit.api.subscription.create')}}">
        <input type="hidden" name="receipt_data" value="fake-receipt-data">
        <input type="hidden" name="password" value="fake-password">
        <input type="hidden" name="user_id" value="{{$user->id}}">
        <div class="bg-pastel p-4 rounded cursor-pointer sandbox-button">
          <p class="mb-2">
            <strong><i class="fas fa-credit-card mr-2"></i>Create subscription</strong>
          </p>
          <span><small>Simulate a request for a new subscription</small></span>
        </div>
      </form>
    </div>
    
    @else

    <div class="col-lg-4 col-md-4 col-sm-8 col-8 p-3">
      <form method="POST" action="{{route('piano-lit.api.subscription.update')}}">
        @include('projects/pianolit/users/show/sandbox/fields', ['event' => 'INITIAL_BUY', 'successful' => true])
        <div class="bg-pastel p-4 rounded cursor-pointer sandbox-button">
          <p class="mb-2">
            <strong><i class="fas fa-cart-arrow-down mr-2"></i>Initial Buy</strong>
          </p>
          <span><small>Simulate the first purchase</small></span>
        </div>
      </form>
    </div>

    <div class="col-lg-4 col-md-4 col-sm-8 col-8 p-3">
      <form method="POST" action="{{route('piano-lit.api.subscription.update')}}">
        @include('projects/pianolit/users/show/sandbox/fields', ['event' => 'RENEWAL', 'successful' => true])
        <div class="bg-intermediate p-4 rounded cursor-pointer sandbox-button">
          <p class="mb-2">
            <strong><i class="fas fa-exchange-alt mr-2"></i>Successful Renewal</strong>
          </p>
          <span><small>Simulate a subscription renewal</small></span>
        </div>
      </form>
    </div>

    <div class="col-lg-4 col-md-4 col-sm-8 col-8 p-3">
      <form method="POST" action="{{route('piano-lit.api.subscription.update')}}">
        @include('projects/pianolit/users/show/sandbox/fields', ['event' => 'RENEWAL', 'successful' => false])
        <div class="bg-beginner p-4 rounded cursor-pointer sandbox-button">
          <p class="mb-2">
            <strong><i class="fas fa-calendar-times mr-2"></i>Failed Renewal</strong>
          </p>
          <span><small>Simulate a cancelled subscription when renewal has failed</small></span>
        </div>
      </form>
    </div>

    <div class="col-lg-4 col-md-4 col-sm-8 col-8 p-3">
      <form method="POST" action="{{route('piano-lit.api.subscription.update')}}">
        @include('projects/pianolit/users/show/sandbox/fields', ['event' => 'DID_CHANGE_RENEWAL_PREF', 'successful' => true])
        <div class="bg-elementary p-4 rounded cursor-pointer sandbox-button">
          <p class="mb-2">
            <strong><i class="fas fa-check-square mr-2"></i>Renewal Preference</strong>
          </p>
          <span><small>Simulate a change of auto renewal preference</small></span>
        </div>
      </form>
    </div>

    <div class="col-lg-4 col-md-4 col-sm-8 col-8 p-3">
      <form method="POST" action="{{route('piano-lit.api.subscription.update')}}">
        @include('projects/pianolit/users/show/sandbox/fields', ['event' => 'CANCEL', 'successful' => true])
        <div class="bg-advanced p-4 rounded cursor-pointer sandbox-button">
          <p class="mb-2">
            <strong><i class="fas fa-trash-alt mr-2"></i>Cancel</strong>
          </p>
          <span><small>Simulate a subscription cancellation</small></span>
        </div>
      </form>
    </div>

    @endif

  </div>
</div>