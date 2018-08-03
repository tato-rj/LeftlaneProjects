<?php

namespace App\Projects\PianoLit;

use App\Projects\PianoLit\Traits\{Resources, PieceExtraAttributes};

class Piece extends PianoLit
{
    use Resources, PieceExtraAttributes;

    protected $casts = ['tips' => 'array'];
    protected $with = ['composer', 'tags'];
    protected $api;
    protected $fillable = ['name'];

    public function __construct()
    {
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

    public function scopeSearch($query, $array, $request)
    {
        $results = $query->where(function($query) use ($array, $request) {

            foreach ($array as $tag) {
                $query->whereHas('tags', function($q) use ($tag) {
                       $q->where('name', 'like', "%$tag%"); 
                });

                if ($request->has('global')) {
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
        
        $results->each(function($piece) {
            $this->api->setCustomAttributes($piece);
        });

        return $results;
    }

    public function scopeFlashy($query)
    {
        $results = $query->whereHas('tags', function($q) {
            $q->where('name', 'flashy');
        })->get();

        $results->each(function($piece) {
            $this->api->setCustomAttributes($piece);
        });

        return $results;
    }

    public function scopeOrnaments($query)
    {
        $results = $query->whereHas('tags', function($q) {
            $q->where('name', 'ornaments');
        })->get();

        $results->each(function($piece, $key) use ($results) {
            if (! in_array($piece->level()->name, ['beginner', 'elementary']))
                $results->forget($key);
        });

        $results->each(function($piece) {
            $this->api->setCustomAttributes($piece);
        });

        return $results;
    }
}
