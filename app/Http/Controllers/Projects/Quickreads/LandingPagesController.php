<?php

namespace App\Http\Controllers;

use App\Subscription;
use Illuminate\Http\Request;

class LandingPagesController extends Controller
{
    public function pianolit()
    {
    	return view('landing-pages/pianolit', ['title' => 'Piano Lit']);
    }

    public function subscribe(Request $request)
    {
    	$request->validate([
    		'email' => 'required|email|unique:subscriptions'
    	]);

    	Subscription::create(['email' => $request->email]);

    	return back()->with('status', 'We\'ll let you know as soon as PianoLIT is available for download :)');
    }

    public function tester()
    {
        $title = 'Piano Lit';
        $message = 'We\'re very excited to have you as a tester for PianoLIT, we\'ll be in touch soon when the prototype is ready.';

        return view('landing-pages/email-feedback', compact(['message', 'title']));

    }

    public function interested()
    {
        $title = 'Piano Lit';
        $message = 'Thank you for your interest! PianoLIT is coming out soon and you\'ll love it. We\'ll keep you in the loop and let you know when it\'s out.';

        return view('landing-pages/email-feedback', compact(['message', 'title']));

    }
}
