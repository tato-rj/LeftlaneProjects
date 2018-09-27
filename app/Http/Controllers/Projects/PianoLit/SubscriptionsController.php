<?php

namespace App\Http\Controllers\Projects\PianoLit;

use App\Http\Requests\VerifySubscriptionForm;
use App\Projects\PianoLit\{Subscription, User};
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;

class SubscriptionsController extends Controller
{
    /**
     * Checks the status of a subscription with Apple
     * @param  Request       $request
     * @param  VerifySubscriptionForm $form
     * @return json        
     */
    public function create(Request $request, VerifySubscriptionForm $form)
    {
        $form->user->subscribe($request);

        if (app()->environment() == 'local')
            return redirect()->back()->with('success', "A susbcription was requested to {$form->user->first_name}'s profile.");

        return response()->json(true);
    }

    /**
     * Updates a susbcription from Apple's notification
     * @param  Request $request
     * @return status code       
     */
    public function update(Request $request)
    {
        $request = app()->environment() == 'testing' ? $request[0] : $request;
        $subscription = Subscription::locate($request->latest_receipt_info->original_transaction_id);
        
        if ($subscription->exists()) 
            $resposne = $subscription->handle($request);

        if (app()->environment() == 'local')
            return redirect()->back()->with('success', "The event notification was posted to {$subscription->user->first_name}'s subscription.");

        return  response(200);
    }

    /**
     * Quickly checks the status of a user (not connecting with Apple's server)
     * @return string
     */
    public function status()
    {
        $user = User::find(request('user_id'));

        if (! $user)
            return response()->json(false);

        $status = $user->status();

        return response()->json(! in_array($status, ['active', 'trial']));
    }

    /**
     * Retrieves entire subscription history from a given user
     * @param  Request $request
     * @return json          
     */
    public function history(Request $request)
    {
        $user = User::find($request->user_id);
        $request['receipt_data'] = $user->subscription->latest_receipt;
        $request['password'] = $user->subscription->password;

        $json = $user->callApple($request);

        $history = json_decode($json);

        return view('projects/pianolit/users/show/subscription/history', compact('history'))->render();
    }

    /**
     * Updates the user's trial period
     * @param  Request $request
     * @return redirect
     */
    public function updateTrial(User $user)
    {
        $newDate = $user->trial_ends_at->gte(now()) ? $user->trial_ends_at->addWeek() : now()->addWeek();

        $user->update(['trial_ends_at' => $newDate]);

        \Mail::to($user->email)->send(new \App\Mail\PianoLit\TrialExtendedEmail($user));

        return redirect()->back()->with('success', "The trial has been update. It now expires on {$newDate->toFormattedDateString()}.");
    }
}
