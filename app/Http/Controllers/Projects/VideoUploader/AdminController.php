<?php

namespace App\Http\Controllers\Projects\VideoUploader;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Projects\VideoUploader\{Admin, Video};

class AdminController extends Controller
{
	public function index()
	{
        $videos = Video::latest()->paginate(6);

        return view('projects.videouploader.index', compact('videos'));
    }
}
