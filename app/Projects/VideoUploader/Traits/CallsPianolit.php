<?php

namespace App\Projects\VideoUploader\Traits;

use Illuminate\Support\Facades\Http;

trait CallsPianolit
{
    public function sendJobProcessedNotification()
    {
        if (! $this->isRemote())
            return null;

        $response = Http::post($this->notification_url, ['video' => $this->toArray()]);

        if ($response->successful())
            $this->update(['notification_received_at' => now()]);

        return $response;
    }

    public function sendVideoDeletedNotification()
    {
        if (! $this->isRemote())
            return null;

        return Http::delete($this->notification_url, ['video' => $this->toArray()]);
    }
}