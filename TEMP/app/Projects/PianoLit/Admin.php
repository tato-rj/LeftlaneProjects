<?php

namespace App\Projects\PianoLit;

use App\Projects\PianoLit\Piece;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    use Notifiable;

    protected $guard = 'pianolit-admin';
    protected $connection = 'pianolit';
    protected $table = 'admins';
    protected $guarded = [];
    protected $hidden = ['password', 'remember_token'];
    protected $withCount = ['pieces', 'composers', 'tags'];

    public function pieces()
    {
        return $this->hasMany(Piece::class, 'creator_id');
    }

    public function composers()
    {
        return $this->hasMany(Composer::class, 'creator_id');
    }

    public function tags()
    {
        return $this->hasMany(Tag::class, 'creator_id');
    }

    public function scopeEditors($query)
    {
        return $query->where('role', 'editor');
    }

    public function scopeManagers($query)
    {
        return $query->where('role', 'manager');
    }
}
