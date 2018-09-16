<?php

namespace App\Http\Controllers\Projects\PianoLit;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use App\Projects\PianoLit\{Subscription, User};
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class SubscriptionsController extends Controller
{
    public function handle(Request $request)
    {

        // VALIDATES
        $validator = \Validator::make($request->all(), [
            'user_id' => 'required',
            'receipt_data' => 'required',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 403);
        }

        // CHECK IF USER EXISTS
        $user = User::find($request->user_id);

        if (! $user)
            return response()->json('User not found!', 403);

        // SUBMITS PAYLOAD TO APPLE
        $client = new Client([
            'headers' => [ 'Content-Type' => 'application/json' ]
        ]);

        $payload = [
            'receipt-data' => $request->receipt_data,
            'password' => $request->password,
            'exclude-old-transactions' => false
        ];

        $response = $client->post('https://sandbox.itunes.apple.com/verifyReceipt', ['body' => json_encode($payload)]);
        
        $response = json_decode($response->getBody(), true);

        if ($response['status'] != 0)
            return response()->json('The receipt did not go through!');

        $receipts = $response['receipt']['in_app'];
        $latestReceipt = $receipts[0];

        // SAVE SUBSCRIPTION ON THE DATABASE
        $method =  $user->subscription()->exists() ? 'update' : 'create';

        $user->subscription()->$method([
            'plan_id' => $latestReceipt['product_id'],
            'expires_at' => $latestReceipt['expires_date'],
            'latest_payment_at' => $latestReceipt['purchase_date'],
            'payments_count' => count($receipts),
        ]);

        $expirationDate = Carbon::parse($latestReceipt['expires_date']);

        $status = $expirationDate->lte(Carbon::now('Etc/GMT'));

        return response()->json($status);
    }

    public function test()
    {
        // SUBMITS PAYLOAD TO APPLE
        $client = new Client([
            'headers' => [ 'Content-Type' => 'application/json' ]
        ]);

        $payload = [
            'receipt-data' => base64_encode('receipt'),
            'password' => '3458c3348153659c0e8b6a072382c88c',
            'exclude-old-transactions' => false
        ];

        $response = $client->post('https://sandbox.itunes.apple.com/verifyReceipt', ['body' => json_encode($payload)]);

        $response = json_decode($response->getBody(), true);

        if ($response['status'] != 0)
            return response()->json('The receipt did not go through!');

        $receipt = $response['receipt'];

        return response()->json($receipt);       
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Subscription  $subscription
     * @return \Illuminate\Http\Response
     */
    public function destroy(Subscription $subscription)
    {
        //
    }
}
