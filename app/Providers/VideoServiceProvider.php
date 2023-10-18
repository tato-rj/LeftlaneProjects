<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Events\JobProcessed;
use App\Projects\VideoUploader\Video;

class VideoServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Queue::after(function (JobProcessed $event) {
            try {
                $video = Video::fromTag(
                    $event->job->payload()['tags'][0]
                );

                if ($video->isRemote()) {
                    \Log::debug('Sending notification back to PianoLIT for video ID: '.$video->id);

                    $video->sendJobProcessedNotification();   
                } else {
                    \Log::debug('The video ID: '.$video->id.' has finished processing');
                }
            } catch (Exception $e) {
                bugsnag();
            }
        });
    }
}
