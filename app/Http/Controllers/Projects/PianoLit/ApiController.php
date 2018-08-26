<?php

namespace App\Http\Controllers\Projects\PianoLit;

use App\Projects\PianoLit\{Piece, Composer, Tag, Api, User};
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ApiController extends Controller
{
    protected $api;

    public function __construct()
    {
        $this->api = new Api;        
    }

    public function piece(Request $request)
    { 
        $piece = Piece::find($request->search);

        $this->api->setCustomAttributes($piece);

        $result[0] = $piece;

        return $result;
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

    public function users()
    {
        return User::latest()->get();
    }

    public function user(User $user)
    {
        $user = $user->load('favorites');

        $user->favorites->each(function($piece) {
            $this->api->setCustomAttributes($piece);
        });

        return $user;        
    }

    public function suggestions(Request $request)
    {
        $user = User::find($request->user_id);

        if (! $user)
            return response()->json(['User not found']);

        $suggestions = $user->suggestions(10);
        
        $suggestions->each(function($piece) {
            $this->api->setCustomAttributes($piece);
        });

        return $suggestions;
    }

    public function getFavorites(Request $request)
    {
        $user = User::find($request->user_id);

        if (! $user)
            return response()->json(['User not found']);

        $favorites = $user->favorites;
        
        $favorites->each(function($piece) {
            $this->api->setCustomAttributes($piece);
        });

        return $favorites;
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

    public function search(Request $request)
    {
        $inputArray = $this->api->prepareInput($request);

        $pieces = Piece::search($inputArray, $request)->get();

        $this->api->prepare($request, $pieces, $inputArray);

        if ($request->wantsJson() || $request->has('api'))
            return $pieces;

        if ($request->has('discover'))
            return $this->discover($pieces, $inputArray);

        $tags = Tag::pluck('name');
                
        return view('projects/pianolit/search/index', compact(['pieces', 'inputArray', 'tags']));
    }
    
    public function discover($pieces = null, $inputArray = null)
    {
        // Collections of playlists
        $composers = $this->api->composers();
        $periods = $this->api->periods();
        $improve = $this->api->improve();
        $levels = $this->api->levels();

        // Collections of pieces
        $suggestions = request()->has('user_id') ? $this->api->forUser(request('user_id')) : [];
        $trending = $this->api->trending();
        $latest = $this->api->latest();
        $famous = $this->api->famous();
        $flashy = $this->api->flashy();

        $collection = compact(['suggestions', 'trending', 'latest', 'composers', 'periods', 'improve', 'levels', 'famous', 'flashy']);

        if (request()->wantsJson() || request()->has('api'))
            return array_values($collection);

        return view('projects/pianolit/discover/index', compact(['collection', 'pieces', 'inputArray']));
    }
}
