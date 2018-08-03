<?php

namespace App\Http\Controllers\Projects\PianoLit;

use App\Projects\PianoLit\{Piece, Composer, Tag, Api};
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ApiController extends Controller
{
    protected $api;

    public function __construct()
    {
        $this->api = new Api;        
    }

    public function pieces()
    {
    	$pieces = Piece::all();
 
        $pieces->each(function($piece) {
            $this->api->setCustomAttributes($piece);
        });

        return $pieces;
    }

    public function composers()
    {
    	$composers = Composer::all();

        $composers->each(function($composer) {
            $this->api->setCustomAttributes($composer);
        });

        return $composers;
    }

    public function tags()
    {
    	return Tag::all();
    }

    public function search(Request $request)
    {
        $inputArray = $this->api->prepareInput($request);

        $pieces = Piece::search($inputArray, $request)->get();

        $this->api->prepare($request, $pieces, $inputArray);

        if ($request->wantsJson() || $request->has('api'))
            return $pieces;

        $tags = Tag::pluck('name');
                
        return view('projects/pianolit/search/index', compact(['pieces', 'inputArray', 'tags']));
    }

    public function tour(Request $request)
    {
        $inputArray = $this->api->prepareInput($request);

        $pieces = Piece::search($inputArray, $request)->get();

        $this->api->prepare($request, $pieces, $inputArray);

        if ($request->wantsJson() || $request->has('api'))
            return $pieces;

        $levels = Tag::levels()->get();
        $lengths = Tag::lengths()->get();
        $moods = Tag::special()->get();
                
        return view('projects/pianolit/tour/index', compact(['pieces', 'inputArray', 'levels', 'lengths', 'moods']));
    }

    public function discover()
    {
        // Collections of playlists
        $composers = $this->api->composers();
        $periods = $this->api->periods();
        $improve = $this->api->improve();
        $countries = $this->api->countries();
        $foundation = $this->api->foundation();
        $levels = $this->api->levels();

        // Collections of pieces
        $trending = $this->api->trending();
        $latest = $this->api->latest();
        $famous = $this->api->famous();
        $flashy = $this->api->flashy();
        $ornaments = $this->api->ornaments();

        $results = compact(['composers', 'periods', 'improve', 'trending', 'flashy', 'countries', 'latest', 'famous', 'foundation', 'ornaments', 'levels']);

        if (request()->wantsJson() || request()->has('api'))
            return $results;

        return view('projects/pianolit/discover/index', $results);
    }
}
