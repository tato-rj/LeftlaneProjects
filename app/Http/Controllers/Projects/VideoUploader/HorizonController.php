<?php

namespace App\Http\Controllers\Projects\VideoUploader;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Horizon\Contracts\JobRepository;
use App\Projects\VideoUploader\Video;

class HorizonController extends Controller
{
    public function status(Video $video)
    {
        return app(JobRepository::class)->getPending();
    }
}
