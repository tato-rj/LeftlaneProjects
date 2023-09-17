<?php

namespace App\Http\Controllers\Projects\VideoUploader;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Projects\VideoUploader\Video;

class WebhookController extends Controller
{
    public function resend(Video $video)
    {
        $response = $video->sendJobProcessedNotification();

        if ($response->ok())
            return back()->with('success', 'The notification was successfully received');

        return back()->with('error', 'Something went wrong. STATUS: '.$response->status());
    }
}
