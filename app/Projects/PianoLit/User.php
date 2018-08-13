<?php

namespace App\Projects\PianoLit;

use App\Projects\PianoLit\Piece;
use Illuminate\Notifications\Notifiable;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    protected $guarded = [];
    protected $connection = 'pianolit';
    protected $hidden = [
        'password', 'remember_token',
    ];
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function favorites()
    {
        return $this->belongsToMany(Piece::class, 'favorites','user_id', 'piece_id');
    }

    public function suggestions($limit)
    {
        $tags = [];

        foreach ($this->favorites as $piece) {
            array_push($tags, $piece->tags->pluck('name'));
        }

        $tags = array_flatten($tags);
        $orderedTags = array_count_values($tags);
        
        arsort($orderedTags);

        $pieces = array_keys(array_slice($orderedTags, 0, 3));

        $suggestions = Piece::search($pieces)->limit($limit)->get();

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
