<?php

namespace App\Http\Controllers\Projects\PianoLit;

use GuzzleHttp\Client;
use App\Projects\PianoLit\{Subscription, User};
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

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
        $client = new Client(['base_uri' => 'https://sandbox.itunes.apple.com/']);

        $response = $client->request('POST', 'verifyReceipt', [
            'receipt-data' => $request->receipt_data,
            'password' => $request->password,
            'exclude-old-transactions' => false
        ]);

        return $response;

        // SAVE SUBSCRIPTION ON THE DATABASE
        // $method =  $user->subscription()->exists() ? 'update' : 'create';

        // $user->subscription()->$method([
        //     'receipt_data' => $request->receipt_data,
        //     'password' => $request->password
        // ]);

        // return $user->subscription;
    }

    public function test()
    {
        // SUBMITS PAYLOAD TO APPLE
        $client = new Client(['base_uri' => 'https://sandbox.itunes.apple.com/']);

        $response = $client->request('POST', 'verifyReceipt', [
            'receipt-data' => '63caa301096b716a1902dcc7ac28a677',
            'password' => '3458c3348153659c0e8b6a072382c88c',
            'exclude-old-transactions' => false
        ]);

        return $response;       
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
