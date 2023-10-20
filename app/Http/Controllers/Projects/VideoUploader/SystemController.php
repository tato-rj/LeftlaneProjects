<?php

namespace App\Http\Controllers\Projects\VideoUploader;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\SystemFiles\SystemFiles;

class SystemController extends Controller
{
    public function index()
    {
        $chunks = (new SystemFiles)->chunks();
        $temporary = (new SystemFiles)->temporary();

        return view('projects.videouploader.system.index', compact(['chunks', 'temporary']));
    }

    public function destroy(Request $request)
    {
        $response = \Artisan::call($request->command);

        return back()->with('success', 'Old files have been removed');
    }
}
