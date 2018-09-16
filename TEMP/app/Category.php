<?php

namespace App;

use App\Story;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
	protected $guarded = [];
	protected $withCount = 'stories';
	
    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function stories()
    {
    	return $this->hasMany(Story::class);
    }
}
