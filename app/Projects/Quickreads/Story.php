<?php

namespace App\Projects\Quickreads;

use App\Projects\Quickreads\{Author, Comment, Rating};
use Illuminate\Support\Facades\File;

class Story extends Quickreads
{
	protected $guarded = [];
    protected $with = ['creator'];
    protected $withCount = ['ratings', 'comments'];
    protected $casts = ['is_classic' => 'boolean'];
	
    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function creator()
    {
        return $this->belongsTo(Author::class, 'author_id', 'id');
    }

    public function image()
    {
        if (File::exists("storage/stories/$this->slug")) {
            if (count(File::allFiles("storage/stories/$this->slug"))) {
                return File::allFiles("storage/stories/$this->slug")[0];
            }
        }

        return "images/no-image.png";
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function favorites()
    {
        return $this->hasMany(UserPurchaseRecord::class, 'story_id');
    }

    public function averageRating()
    {
        if (!$this->ratings_count) return '0';
        $scores = $this->ratings()->pluck('score')->toArray();

        return round(array_sum($scores) / count($scores)); 
    }

    public function scopeFormatted($query)
    {
        return $query->join('categories', 'stories.category_id', '=', 'categories.id')
                     ->join('authors', 'stories.author_id', '=', 'authors.id')
                     ->selectRaw('CAST(stories.id as CHAR(100)) as id, authors.name AS author, CONCAT("(",authors.born_in," - ",authors.died_in,")") AS dates, authors.life AS life, stories.title AS title, stories.summary AS summary, stories.time AS time, stories.cost AS cost, CONCAT("https://leftlaneapps.com/storage/stories/",stories.slug,"/",stories.slug,".jpeg") AS story_filename, categories.category AS category, categories.sorting_order');
    }

    public function favoritedBy($facebook_id)
    {
        $user = User::where('facebook_id', $facebook_id)->first();
        
        return $user ?
                UserPurchaseRecord::where(['user_id' => $user->id, 'story_id' => $this->id])->exists() :
                false;
    }
}
