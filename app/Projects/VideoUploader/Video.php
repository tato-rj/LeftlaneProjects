<?php

namespace App\Projects\VideoUploader;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Projects\VideoUploader\Traits\CallsPianolit;
use Illuminate\Http\UploadedFile;
use App\VideoProcessor\VideoProcessor;

class Video extends Model
{
    use HasFactory, CallsPianolit;

    protected $connection = 'videouploader';
    protected $guarded = [];
    protected $dates = ['completed_at'];
    protected $appends = ['video_url'];

    public function getNotificationUrlAttribute()
    {
        return env('PIANOLIT_NOTIFICATION_URL');
    }

    public function getVideoUrlAttribute()
    {
        if ($this->video_path)
            return storage($this->video_path);
    }

    public function composer()
    {
        if (! $this->video_path)
            return null;

        $part = explode('/', $this->video_path)[0];

        $unslugged = ucwords(str_replace('-', ' ', $part));

        return $unslugged;
    }

    public function filename()
    {
        if (! $this->video_path)
            return null;

        $part = explode('/', $this->video_path)[1];

        $filenameWithoutExtension = pathinfo($part, PATHINFO_FILENAME);

        return $filenameWithoutExtension;
    }

    public function getOriginalSizeMbAttribute()
    {
        return round($this->original_size / 1000000, 1);
    }

    public function scopeFromTag($query, $tag)
    {
        $pieces = explode(':', $tag);

        $class = $pieces[0];
        $id = $pieces[1];

        if ($class != get_class($this))
            abort(404, 'This job was not for a Video');

        return $query->find($id);
    }

    public function markAsCompleted()
    {
        $this->update([
            'completed_at' => now()
        ]);
    }

    public function belongsToPayload($payload)
    {
        $data = json_decode($payload);

        if (! property_exists($data, 'tags'))
            return false;

        $pieces = explode(':', $data->tags[0]);

        $id = $pieces[1];

        return $this->id == $id;
    }

    public function scopeTemporary($query, $file, array $request)
    {
        // return $query->create([
        //     'origin' => $request['origin'],
        //     'piece_id' => $request['piece_id'],
        //     'user_id' => $request['user_id'],
        //     'user_email' => $request['email'],
        //     'notes' => $request['notes'],
        //     'temp_path' => \Storage::disk('public')->put('temporary', $file),
        //     'original_size' => $file->getSize()
        // ]);
    }

    public function scopeFilters($query, $request)
    {
        if ($origin = $request->origin)
            $query->$origin();

        if ($state = $request->state)
            $query->$state();

        return $query;
    }

    public function isCompleted()
    {
        return (bool) $this->completed_at;
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }

    public function isFailed()
    {
        return (bool) $this->failed_at;
    }

    public function scopeFailed($query)
    {
        return $query->whereNotNull('failed_at');
    }

    public function isPending()
    {
        return ! $this->isCompleted() && ! $this->isFailed() && ! $this->isAbandoned();
    }

    public function scopePending($query)
    {
        return $query->whereNull(['failed_at', 'completed_at', 'abandoned_at']);
    }

    public function isAbandoned()
    {
        return (bool) $this->abandoned_at;
    }

    public function scopeAbandoned($query)
    {
        return $query->whereNotNull('abandoned_at');
    }

    public function finish(VideoProcessor $processor)
    {
        $this->update([
            'video_path' => $processor->path()->video(),
            'thumb_path' => $processor->path()->thumbnail(),
            'compressed_size' => \Storage::disk('gcs')->size($processor->path()->video()),
            'mimeType' => \Storage::disk('gcs')->mimeType($processor->path()->video())
        ]);        

        return $this->markAsCompleted();
    }
}
