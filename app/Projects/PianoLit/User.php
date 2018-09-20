<?php

namespace App\Projects\PianoLit;

use App\Projects\PianoLit\{Piece, Subscription};
use App\Projects\PianoLit\Traits\{Resources, Subscribes};
use Illuminate\Notifications\Notifiable;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable, Resources, Subscribes;

    protected $guarded = [];
    protected $connection = 'pianolit';
    protected $hidden = [
        'password', 'remember_token',
    ];
    protected $casts = [
        'is_active' => 'boolean',
    ];
    protected $dates = [
        'trial_ends_at'
    ];

    public function favorites()
    {
        return $this->belongsToMany(Piece::class, 'favorites','user_id', 'piece_id');
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class);
    }

    public function getPreferredPieceAttribute()
    {
        return Piece::find($this->preferred_piece_id);
    }

    public function tags()
    {
        $tags = $this->preferred_piece->tags->pluck('name')->toArray();

        foreach ($this->favorites as $piece) {
            array_push($tags, $piece->tags->pluck('name'));
        }

        $tags = array_flatten($tags);
        $orderedTags = array_count_values($tags);
        
        arsort($orderedTags);

        return array_keys(array_slice($orderedTags, 0, 3));       
    }

    public function suggestions($limit)
    {
        $tags = $this->tags();

        $suggestions = Piece::search($tags)->limit($limit)->get();

        $suggestions->each(function($piece, $key) use ($suggestions) {
            if ($this->favorites->contains($piece))
                $suggestions->forget($key);
        });

        return $suggestions;
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
}
