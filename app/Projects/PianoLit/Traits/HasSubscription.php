<?php

namespace App\Projects\PianoLit\Traits;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Projects\PianoLit\Providers\Sandbox\AppleSubscription;

trait HasSubscription
{
    public function subscribe(Request $request)
    {
        $json = $this->callApple($request->receipt_data, $request->password);

        $response = json_decode($json);

        $plan = $response->receipt->in_app[0]->product_id;

        $due_date = $this->getDueDate($plan);

        $record = $this->subscription()->create([
            'plan' => $plan,
            'latest_receipt' => $request->receipt_data,
            'latest_receipt_info' => json_encode($response->receipt->in_app[0]),
            'password' => $request->password,
            'renews_at' => $due_date
        ]);

        $this->update(['trial_ends_at' => null]);

        return $record;
    }

    public function getDueDate($plan)
    {
        return $plan == 'monthly' ? now()->addMonth() : now()->addYear();
    }

    public function getStatus($callApple = false)
    {
        if (! $this->subscription()->exists() && $this->trial_ends_at->gte(now()))
            return 'trial';

        if (! $this->subscription()->exists() && $this->trial_ends_at->lt(now()))
            return 'expired';

        if (! $this->subscription->expired())
            return 'active';

        if (! $callApple)
            return 'inactive';

        $request = $this->callApple($this->subscription->receipt_data, $this->subscription->password);

        $request = json_decode($request);

        $latest_receipt = end($request->receipt->in_app);
        
        $is_valid = $latest_receipt->expires_date_ms >= now()->timestamp;

        if (! $is_valid)
            return 'inactive';

        $this->subscription->reactivate($latest_receipt);

        return 'active';
    }

    public function callApple($receipt_data, $password)
    {
        $client = new Client([
            'headers' => ['Content-Type' => 'application/json']
        ]);

        $payload = json_encode([
            'receipt-data' => $receipt_data,
            'password' => $password,
            'exclude-old-transactions' => false
        ]);

        $response = app()->environment() != 'production' 
            ? (new AppleSubscription)->generate() 
            : $client->post('https://sandbox.itunes.apple.com/verifyReceipt', ['body' => $payload])->getBody();

        return $response;
    }
}
