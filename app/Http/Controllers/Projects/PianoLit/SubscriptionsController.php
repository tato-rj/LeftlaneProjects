<?php

namespace App\Http\Controllers\Projects\PianoLit;

use App\Http\Requests\VerifySubscriptionForm;
use App\Projects\PianoLit\{Subscription, User, Admin};
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
     * Checks the status of a user (app only)
     * @return string
     */
    public function status(Request $request)
    {
        $user = User::find($request->user_id);

        if (! $user)
            return response()->json(false);

        $status = $user->getStatus($callApple = true);

        return response()->json($status);
    }

    /**
     * Verify and validate the status of a subscription (admin only)
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function verify(Request $request)
    {
        $user = User::find($request->user_id);

        if (! $user)
            return redirect()->back()->with('error', "Sorry, we couldn't find the user");

        $request = $user->callApple($user->subscription->latest_receipt, $user->subscription->password);

        $user->subscription->validate($request);
    
        return redirect()->back()->with('success', "The has been successfully re-validated.");
    }

    /**
     * Verify and validate the status of a subscription (admin only)
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function verifyAll(Request $request)
    {
        $admin = Admin::find($request->admin_id);

        if (! $admin)
            return redirect()->back()->with('error', "You are not authorized to do this.");

        $users = User::expired();

        if ($users->isEmpty())
            return redirect()->back()->with('error', "We found no expired subscriptions.");

        foreach ($users as $user) {
            $request = $user->callApple($user->subscription->latest_receipt, $user->subscription->password);

            $user->subscription->validate($request);   
        }
    
        return redirect()->back()->with('success', "All users have been successfully re-validated.");
    }

    /**
     * Retrieves entire subscription history from a given user
     * @param  Request $request
     * @return json          
     */
    public function history(Request $request)
    {
        $user = User::find($request->user_id);

        $receipt = $user->callApple($user->subscription->latest_receipt, $user->subscription->password);

        $history = $user->cleanReceipt($receipt);

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

    /**
     * Remove a user's subscription record (local environment only)
     * @param  User   $user 
     * @return redirect
     */
    public function destroy(User $user)
    {
        if (app()->environment() !== 'local')
            return null;
        
        $user->subscription()->delete();
        $user->update(['trial_ends_at' => now()->addWeek()]);

        return redirect()->back()->with('success', "The subscription has been successfully removed.");
    }
}
