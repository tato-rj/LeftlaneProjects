<?php

namespace App\Http\Controllers\Projects\VideoUploader;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Projects\VideoUploader\{Admin, Video};

class AdminController extends Controller
{
	public function index(Request $request)
	{
        $videos = Video::latest()->filters($request)->paginate(12);
// return $videos->first();
        return view('projects.videouploader.index', compact(['videos']));
    }
}
