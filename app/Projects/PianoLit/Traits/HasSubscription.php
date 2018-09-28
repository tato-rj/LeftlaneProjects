<?php

namespace App\Projects\PianoLit\Traits;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Projects\PianoLit\Providers\Sandbox\AppleSubscription;
use Carbon\Carbon;

trait HasSubscription
{
    public function subscribe(Request $request)
    {
        $json = $this->callApple($request->receipt_data, $request->password);

        $response = json_decode($json);

        $latest_receipt = $response->receipt->in_app[0];

        $record = $this->subscription()->create([
            'plan' => $latest_receipt->product_id,
            'latest_receipt' => $request->receipt_data,
            'latest_receipt_info' => json_encode($latest_receipt),
            'password' => $request->password,
            'renews_at' => Carbon::parse($latest_receipt->expires_date)->timezone(config('app.timezone')),
            'validated_at' => now()
        ]);

        $this->update(['trial_ends_at' => null]);

        return $record;
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

        $request = $this->callApple($this->subscription->latest_receipt, $this->subscription->password);

        return $this->subscription->validate($request);
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

    public function cleanReceipt($request)
    {
        $request = json_decode($request);

        foreach ($request->receipt as $field => $value) {
            if (preg_match('(pst|ms)', $field) === 1 || is_null($value))
                unset($request->receipt->$field);   
        }

        foreach ($request->receipt->in_app as $receipt) {
            foreach ($receipt as $field => $value) {
                if (preg_match('(pst|ms)', $field) === 1 || is_null($value))
                    unset($receipt->$field);   
            }
        }

        return $request;
    }

    public function scopeExpired($query)
    {
        $users = $query->has('subscription')->get();

        foreach ($users as $index => $user) {
            if (! $user->subscription->expired())
                $users->forget($index);
        }

        return $users;
    }
}
