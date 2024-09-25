<?php

namespace App\Http\Controllers\Projects\VideoUploader;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Projects\VideoUploader\{Admin, Video};

class AdminController extends Controller
{
	public function index(Request $request)
	{
                if ($request->sort_by == 'composer') {
                        $videos = Video::orderBy('video_path')->filters($request)->paginate(12);
                } else {
                        $videos = Video::latest()->filters($request)->paginate(12);
                }
        
// return $videos->first();
        return view('projects.videouploader.videos.index', compact(['videos']));
    }
}
