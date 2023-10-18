<?php

namespace App\Http\Controllers\Projects\VideoUploader;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Horizon\Contracts\JobRepository;
use App\Projects\VideoUploader\Video;
use App\Jobs\ProcessVideo;

class HorizonController extends Controller
{
    public function status(Video $video)
    {
        if (! $video->failed()) {
            $records = app(JobRepository::class)->getFailed();
    
            foreach ($records as $record) {
                if ($video->belongsToPayload($records->first()->payload)) {
                    $video->markAsFailed();

                    return back()->with('success', 'This video failed');
                }
            }

            $video->update(['failed_at' => null]);
        }

        if ($video->pending()) {
            $records = app(JobRepository::class)->getPending();
    
            foreach ($records as $record) {
                if ($video->belongsToPayload($records->first()->payload)) {
                    $video->markAsPending();

                    return back()->with('success', 'This video is pending');
                }
            }

            $video->markAsAbandoned();
        }

        if ($video->abandoned())
            return back()->with('success', 'The video has was abandoned and could not be processed');

        return back()->with('success', 'The status of this video has not changed');
    }

    public function retry(Video $video)
    {
        ProcessVideo::dispatch($video);

        return back()->with('success', 'The video is back in the queue');
    }
}
