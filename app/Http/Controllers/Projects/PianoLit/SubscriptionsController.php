<?php

namespace App\Http\Controllers\Projects\PianoLit;

use App\Http\Requests\SubscribeUser;
use App\Projects\PianoLit\Providers\Apple;
use App\Projects\PianoLit\{Subscription, User};
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class SubscriptionsController extends Controller
{
    public function handle(Request $request, SubscribeUser $form)
    {
        $response = (new Apple)->prepare($request)->call();

        if ($response['status'] != 0)
            return response()->json('The receipt did not go through!');

        $user = User::find($request->user_id);

        $user->subscribe($response['receipt']);

        return response()->json($user->subscription->status());
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
