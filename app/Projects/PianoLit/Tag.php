<?php

namespace App\Projects\PianoLit;

class Tag extends PianoLit
{
    public function creator()
    {
        return $this->belongsTo(Admin::class);
    }

    public function pieces()
    {
        return $this->belongsToMany(Piece::class);
    }

    public function scopeSpecial($query)
    {
        return $query->where('type', 'special');
    }
    
    public function scopeLevels($query)
    {
    	return $query->where('type', 'level');
    }
    
    public function scopeImprove($query)
    {
        return $query->whereIn('name', ['scales', 'arpeggios', 'octaves', 'thirds', 'fifths', 'fourths', 'right hand', 'left hand']);
    }

    public function scopeLengths($query)
    {
    	return $query->where('type', 'length');
    }

    public function scopePeriods($query)
    {
    	return $query->where('type', 'period');
    }

    public function scopeFamous($query)
    {
        return $query->where('name', 'famous');
    }

}
