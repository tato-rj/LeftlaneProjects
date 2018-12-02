<?php

namespace App\Projects\PianoLit;

use App\Projects\PianoLit\Traits\{Resources, PieceExtraAttributes, BelongsToThrough};

class Piece extends PianoLit
{
    use Resources, PieceExtraAttributes, BelongsToThrough;

    protected $casts = ['tips' => 'array'];
    protected $with = ['composer', 'tags'];
    protected $appends = ['medium_name', 'recordingsAvailable'];
    protected $api;

    public function __construct($attributes = [])
    {
        parent::__construct($attributes);
        $this->api = new Api;        
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function playlists()
    {
        return $this->belongsToMany(Playlist::class);
    }

    public function level()
    {
        return $this->belongsToMany(Tag::class)->where('type', 'level')->first();
    }

    public function length()
    {
        return $this->belongsToMany(Tag::class)->where('type', 'length')->first();
    }

    public function period()
    {
        return $this->belongsToMany(Tag::class)->where('type', 'period')->first();
    }

    public function composer()
    {
    	return $this->belongsTo(Composer::class);
    }

    public function country()
    {
        return $this->belongsToThrough(Country::class, Composer::class);
    }

    public function hasAudio()
    {
        return $this->audio_path || $this->audio_path_lh || $this->audio_path_rh;
    }

    public function getRecordingsAvailableAttribute()
    {
        $count = 0;

        if ($this->audio_path) $count += 1;
        if ($this->audio_path_rh) $count += 1;
        if ($this->audio_path_lh) $count += 1;

        return $count;
    }

    public function scopeByRecordingsAvailable($query)
    {
        return $query->get()->groupBy('recordingsAvailable')->each(function($group) {
            $group['count'] = $group->count();
        });
    }

    public function isFavorited($user_id)
    {
        $result = \DB::connection('pianolit')->table('favorites')->where([
            'user_id' => $user_id,
            'piece_id' => $this->id
        ])->first();

        return $result ? true : false;
    }

    public function scopeSearch($query, $array, $request = null)
    {
        $results = $query->where(function($query) use ($array, $request) {

            foreach ($array as $tag) {
                $query->whereHas('tags', function($q) use ($tag) {
                       $q->where('name', 'like', "%$tag%"); 
                });

                if (is_null($request) || $request->has('global')) {
                    $query->orWhereHas('composer', function($q) use ($tag) {
                           $q->where('name', 'like', "%$tag%");
                    })->orWhere('nickname', 'like', "%$tag%")
                      ->orWhere('name', 'like', "%$tag%")
                      ->orWhere('collection_name', 'like', "%$tag%")
                      ->orWhere('catalogue_name', $tag)
                      ->orWhere('catalogue_number', $tag);
                }
            }
            
        });
        
        return $results;
    }

    public function scopeFamous($query)
    {
        $results = $query->whereHas('tags', function($q) {
            $q->where('name', 'famous');
        })->get();
        
        return $results;
    }

    public function scopeFlashy($query)
    {
        $results = $query->whereHas('tags', function($q) {
            $q->where('name', 'flashy');
        })->get();

        return $results;
    }

    public function scopeSuggestedTips($query, $request)
    {
        $tagsArray = explode(',', $request->tags);

        return $query->whereHas('tags', function($query) use ($tagsArray) {
                    $query->whereIn('id', $tagsArray);
                })->pluck('tips');
    }
}
