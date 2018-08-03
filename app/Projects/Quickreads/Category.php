<?php

namespace App\Projects\Quickreads;

use App\Projects\Quickreads\Story;

class Category extends Quickreads
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
