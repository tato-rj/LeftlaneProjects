<?php

namespace App\Projects\Quickreads;

class UserPurchaseRecord extends Quickreads
{
	protected $appends = ['story_title'];
    protected $guarded = [];

    public function story()
    {
    	return $this->belongsTo(Story::class);
    }

    public function getStoryTitleAttribute()
    {
    	return $this->story->title;
    }
}
