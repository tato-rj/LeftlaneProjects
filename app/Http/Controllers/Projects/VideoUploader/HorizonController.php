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
        if (! $video->failed_at) {
            $records = app(JobRepository::class)->getFailed();
    
            foreach ($records as $record) {
                if ($video->belongsToPayload($records->first()->payload)) {
                    $video->markAsFailed();

                    return back()->with('success', 'This video failed');
                }
            }
        }

        return back()->with('success', 'The status of this video has not changed');
    }
}
