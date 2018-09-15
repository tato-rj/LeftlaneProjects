<?php

namespace App\Http\Controllers\Projects\PianoLit;

use App\Projects\PianoLit\{Subscription, User};
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SubscriptionsController extends Controller
{
    public function handle(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'user_id' => 'required',
            'receipt_data' => 'required',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 403);
        }

        $user = User::find($request->user_id);

        if (! $user)
            return response()->json('User not found!', 403);

        $method =  $user->subscription()->exists() ? 'update' : 'create';

        $user->subscription()->$method([
            'receipt_data' => $request->receipt_data,
            'password' => $request->password
        ]);

        return $user->subscription;
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
