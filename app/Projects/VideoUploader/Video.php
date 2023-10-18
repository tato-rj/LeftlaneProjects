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
    protected $dates = [
        'completed_at', 
        'abandoned_at', 
        'notification_received_at', 
        'failed_at'
    ];
    protected $appends = ['video_url', 'thumb_url'];

    public function getNotificationUrlAttribute()
    {
        return env('PIANOLIT_NOTIFICATION_URL');
    }

    public function getVideoUrlAttribute()
    {
        if ($this->video_path)
            return \Storage::disk('gcs')->url($this->video_path);
    }

    public function getThumbUrlAttribute()
    {
        if ($this->thumb_path)
            return \Storage::disk('gcs')->url($this->thumb_path);
    }

    public function getOriginalSizeMbAttribute()
    {
        return round($this->original_size / 1000000, 1) . 'mb';
    }

    public function getCompressedSizeMbAttribute()
    {
        return round($this->compressed_size / 1000000, 1) . 'mb';
    }

    public function getSizeDecreasePercentageAttribute()
    {
        $diff = $this->original_size - $this->compressed_size;
        $percentage = round(($diff * 100) / $this->original_size) * -1;

        return $percentage > 0 ? '+'.$percentage.'%' : $percentage.'%';
    }

    public function getProcessingTimeAttribute()
    {
        if ($this->isCompleted())
            return gmdate('i:s', $this->completed_at->diffInSeconds($this->created_at));
    }

    public function getOriginIconAttribute()
    {
        switch ($this->origin) {
            case 'webapp':
                return 'fas fa-laptop';
                break;

            case 'ios':
                return 'fab fa-apple';
                break;

            case 'android':
                return 'fas fa-mobile';
                break;

            default:
                return $this->origin ?? 'question';
                break;
        }
    }

    public function getOrientationAttribute()
    {
        $dimensions = $this->dimensions();

        return $dimensions[0] > $dimensions[1] ? 'landscape' : 'portrait';
    }

    public function dimensions()
    {
        return explode('x', $this->compressed_dimensions);
    }

    public function scopeRemote($query)
    {
        return $query->whereIn('origin', ['webapp', 'ios']);
    }

    public function scopeLocal($query)
    {
        return $query->where('origin', 'local');
    }

    public function isRemote()
    {
        return in_array($this->origin, ['webapp', 'ios']);
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

    public function markAsFailed()
    {
        $this->update([
            'completed_at' => null,
            'abandoned_at' => null,
            'failed_at' => now()
        ]);
    }

    public function markAsAbandoned()
    {
        $this->update([
            'completed_at' => null,
            'failed_at' => null,
            'abandoned_at' => now()
        ]);
    }

    public function markAsPending()
    {
        $this->update([
            'abandoned_at' => null,
            'failed_at' => null,
            'completed_at' => null
        ]);
    }

    public function markAsCompleted()
    {
        $this->update([
            'abandoned_at' => null,
            'failed_at' => null,
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
        return $query->create([
            'origin' => $request['origin'],
            'piece_id' => $request['piece_id'],
            'user_id' => $request['user_id'],
            'user_email' => $request['email'],
            'notes' => $request['notes'],
            'temp_path' => \Storage::disk('public')->put('temporary', $file),
            'original_size' => $file->getSize()
        ]);
    }

    public function scopeFilters($query, $request)
    {
        if ($filter = $request->filter)
            $query->$filter();

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
        return ! $this->isCompleted() && ! $this->isFailed();
    }

    public function scopePending($query)
    {
        return $query->whereNull(['failed_at', 'completed_at']);
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
