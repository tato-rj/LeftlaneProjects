<?php

namespace App\Projects\Quickreads;

use App\Projects\Quickreads\{Comment, Rating, UserPurchaseRecord};
use App\Projects\Quickreads\Records\Records;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    protected $guarded = [];

    protected $connection = 'quickreads';

    protected $table = 'users';

    protected $hidden = [
        'password', 'remember_token',
    ];

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function getFullNameAttribute()
    {
        return "$this->first_name $this->last_name";
    }

    public function getProfilePictureAttribute()
    {
        if ($this->password)
            return asset('images/default_avatar.png');

        return "http://graph.facebook.com/{$this->facebook_id}/picture?type=large";
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function purchases()
    {
        return $this->hasMany(UserPurchaseRecord::class);
    }

    public function stories()
    {
        return $this->belongsToMany(Story::class, 'user_purchase_records', 'user_id', 'story_id')->select(['stories.id', 'title','user_purchase_records.created_at'])->latest();
    }

    public function ratingsFor($storyId)
    {
        $rating =  Rating::where([
            'user_id' => $this->id,
            'story_id' => $storyId
        ])->first();

        return ($rating) ? $rating->score : 0;
    }

    public function scopeStatistics($query)
    {
        return new Records($this);
    }
}
