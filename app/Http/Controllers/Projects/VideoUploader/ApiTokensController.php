<?php

namespace App\Http\Controllers\Projects\VideoUploader;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApiTokensController extends Controller
{
    public function index()
    {
        return view('projects.videouploader.tokens');
    }

    public function store()
    {
        auth()->user()->createToken(\Hash::make(auth()->user()->email.now()->timestamp));

        return back()->with('success', 'The token was successfully created');
    }

    public function revoke(Request $request)
    {
        auth()->user()->tokens()->where('id', $request->id)->delete();

        return back()->with('success', 'The token was successfully revoked');
    }
}
