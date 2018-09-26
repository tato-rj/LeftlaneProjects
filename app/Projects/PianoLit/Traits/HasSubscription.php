<?php

namespace App\Projects\PianoLit\Traits;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Projects\PianoLit\Providers\Sandbox\AppleSubscription;

trait HasSubscription
{
    public function subscribe(Request $request)
    {
        $receipt = $this->callApple($request);

        $record = $this->subscription()->create([
            'original_transaction_id' => $receipt['receipt']['in_app'][0]['original_transaction_id'],
            'notification_type' => 'pending',
            'latest_receipt' => $request->receipt_data,
            'latest_receipt_info' => json_encode($receipt['receipt']['in_app'][0]),
            'password' => $request->password
        ]);

        $this->update(['trial_ends_at' => null]);

        return $record;
    }

    public function status()
    {
        if (! $this->subscription()->exists() && $this->trial_ends_at->gte(now()))
            return 'trial';

        if (! $this->subscription()->exists() && $this->trial_ends_at->lt(now()))
            return 'expired';

        if ($this->subscription->notification_type == 'pending')
            return 'pending';

        return $this->subscription->expired() || $this->subscription->cancelled() ? 'inactive' : 'active';
    }

    public function callApple(Request $request)
    {
        $client = new Client([
            'headers' => ['Content-Type' => 'application/json']
        ]);

        $payload = json_encode([
            'receipt-data' => $request->receipt_data,
            'password' => $request->password,
            'exclude-old-transactions' => false
        ]);

        return app()->environment() != 'production' ? 
                (new AppleSubscription)->generate()
                : $client->post(config('services.apple.address'), ['body' => $payload]); 
    }
}
