<?php

namespace App\Http\Controllers\Projects\VideoUploader;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Projects\VideoUploader\{Admin, Video};

class AdminController extends Controller
{
	public function index(Request $request)
	{
        $videos = Video::latest()->filters($request)->paginate(6);

        $sentences = ['Tuning the piano', 'Arranging rows of comfy seats', 'Adjusting the bench', 'Warming up fingers', 'Greeting the eager audience', 'Dimming the lights', 'Wrapping up'];

        return view('projects.videouploader.index', compact(['videos', 'sentences']));
    }
}
