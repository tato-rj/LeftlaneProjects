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

        $records = app(JobRepository::class)->getFailed();

        foreach ($records as $record) {
            if ($video->belongsToPayload($records->first()->payload)) {
                $video->markAsFailed();

                return back()->with('success', 'This video failed');
            }
        }

        $video->update(['failed_at' => null]);

        $records = app(JobRepository::class)->getPending();
return $records;
        foreach ($records as $record) {
            if ($video->belongsToPayload($records->first()->payload)) {
                $video->markAsPending();

                return back()->with('success', 'This video is pending');
            }
        }

        $records = app(JobRepository::class)->getCompleted();

        foreach ($records as $record) {
            if ($video->belongsToPayload($records->first()->payload)) {
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
