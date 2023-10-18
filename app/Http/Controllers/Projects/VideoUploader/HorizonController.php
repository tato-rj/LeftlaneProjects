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

        $failed = app(JobRepository::class)->getFailed();

        foreach ($failed as $record) {
            if ($video->belongsToPayload($failed->first()->payload)) {
                $video->markAsFailed();

                return back()->with('success', 'This video failed');
            }
        }

        $video->update(['failed_at' => null]);

        $pending = app(JobRepository::class)->getPending();
return $video->belongsToPayload($pending[2]->payload);
        foreach ($pending as $record) {
            if ($video->belongsToPayload($pending->first()->payload)) {
                $video->markAsPending();

                return back()->with('success', 'This video is pending');
            }
        }

        $completed = app(JobRepository::class)->getCompleted();

        foreach ($completed as $record) {
            if ($video->belongsToPayload($completed->first()->payload)) {
                $video->markAsCompleted();

                return back()->with('success', 'This video has successfully finished');
            }
        }

        $video->markAsAbandoned();

        return back()->with('success', 'The video has was abandoned and could not be processed');
    }

    public function retry(Video $video)
    {
        ProcessVideo::dispatch($video);

        $video->markAsPending();

        return back()->with('success', 'The video is back in the queue');
    }
}
