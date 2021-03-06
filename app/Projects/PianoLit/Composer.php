<?php

namespace App\Projects\PianoLit;

class Composer extends PianoLit
{
    protected $dates = ['date_of_birth', 'date_of_death'];
    protected $appends = ['last_name', 'short_name'];
    protected $withCount = ['pieces'];

    public function creator()
    {
        return $this->belongsTo(Admin::class);
    }

    public function pieces()
    {
    	return $this->hasMany(Piece::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function getNationalityAttribute()
    {
        return $this->country->nationality;
    }

    public function getPeriodAttribute($nationality)
    {
        return ucfirst($nationality);
    }

    public function getBornAtAttribute()
    {
        return $this->date_of_birth->toFormattedDateString();
    }

    public function getDiedAtAttribute()
    {
        return $this->date_of_death->toFormattedDateString();
    }

    public function getAliveOnAttribute()
    {
    	return "{$this->date_of_birth->year} - {$this->date_of_death->year}";
    }

    public function getShortNameAttribute()
    {
        $namesArray = explode(' ', $this->name);

        $initials = '';

        $lastName = array_pop($namesArray);

        foreach ($namesArray as $name) {
            $initials .= $name[0].'.';
        }

        return "$initials$lastName";
    }

    public function getLastNameAttribute()
    {
        $nameArray = explode(' ', $this->name);
        return array_pop($nameArray);
    }
}
