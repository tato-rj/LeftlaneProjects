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
        $pending = app(JobRepository::class)->getPending();

        foreach ($pending as $record) {
            if ($video->belongsToPayload($record->payload)) {
                $video->markAsPending();

                return back()->with('success', 'This video is pending');
            }
        }

        $failed = app(JobRepository::class)->getFailed();

        foreach ($failed as $record) {
            if ($video->belongsToPayload($record->payload)) {
                $video->markAsFailed();

                return back()->with('success', 'This video failed');
            }
        }

        $completed = app(JobRepository::class)->getCompleted();

        foreach ($completed as $record) {
            if ($video->belongsToPayload($record->payload)) {
                $video->markAsCompleted();

                return back()->with('success', 'This video has successfully finished');
            }
        }

        $video->markAsAbandoned();

        return back()->with('success', 'The video was abandoned and could not be processed');
    }

    public function retry(Video $video)
    {
        if ($video->isAbandoned())
            ProcessVideo::dispatch($video);

        if ($video->isFailed()) {
            dd('here');
        }

        $video->markAsPending();

        return back()->with('success', 'The video is back in the queue');
    }
}
