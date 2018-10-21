<?php

namespace App\Http\Controllers\Projects\PianoLit;

use App\Projects\PianoLit\{Piece, Composer, Tag, Country, Playlist};
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PiecesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $filtersArray = ['level', 'period', 'length'];
        $sortArray = ['asc', 'desc'];

        $request = array_keys(request()->toArray());

        if (request()->has('order') && in_array(request('order'), $sortArray))
            $sort = request('order');

        $pieces = new Piece;

        foreach ($request as $filter) {
            if ($filter == 'composer')
                $pieces = $pieces->where('composer_id', request($filter));

            if ($filter == 'creator_id')
                $pieces = $pieces->where('creator_id', request($filter));

            if (in_array($filter, $filtersArray)) {
                $pieces = $pieces->whereHas('tags', function($piece) use ($filter) {
                    $piece->where('name', request($filter));
                });
            }
        }

        $pieces = $pieces->orderBy('catalogue_number', $sort ?? 'asc')->paginate(20);

        return view('projects/pianolit/pieces/index', compact('pieces'));
    }

    public function search()
    {
        $inputArray = [];
        $tags = Tag::pluck('name');

        return view('projects/pianolit/search/index', compact(['tags', 'inputArray']));
    }

    public function singleLookup(Request $request)
    {
        $field = $request->field;

        $results = Piece::selectRaw("$field, $field as output")
                        ->where($field, 'like', "%$request->input%")
                        ->groupBy($field)
                        ->get();

        return $results;
    }
   
    public function multiLookup(Request $request)
    {
        $results = Piece::selectRaw('collection_name, catalogue_name, catalogue_number, composer_id, 
            CONCAT_WS(" ", collection_name, catalogue_name, catalogue_number) as output')
                        ->where('collection_name', 'like', "%$request->input%")
                        ->groupBy('collection_name', 'catalogue_name', 'catalogue_number', 'composer_id')
                        ->get();

        return $results;
    }

    public function validateName(Request $request)
    {
        $result = Piece::where([
            ['name', '=', $request->name ?? null],
            ['catalogue_number', '=', $request->catalogue_number ?? null],
            ['collection_number', '=', $request->collection_number ?? null]
        ])->first();

        return $result->medium_name ?? null;        
    }

    public function tour()
    {
        $levels = Tag::levels()->get();
        $lengths = Tag::lengths()->get();
        $moods = Tag::special()->get();
        
        return view('projects/pianolit/tour/index', compact(['levels', 'lengths', 'moods']));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $composers = Composer::orderBy('name')->get();
        $types = Tag::byTypes($except = ['levels', 'periods', 'lengths']);

        return view('projects/pianolit/pieces/create', compact(['composers', 'types']));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:255',
            'key' => 'required',
            'composer_id' => 'required',
            'period' => 'required',
            'key' => 'required',
            'length' => 'required',
            'level' => 'required',
        ]);

        $piece = Piece::create([
            'name' => $request->name,
            'nickname' => $request->nickname,
            'collection_name' => $request->collection_name,
            'collection_number' => $request->collection_number,
            'catalogue_name' => $request->catalogue_name,
            'catalogue_number' => $request->catalogue_number,
            'movement_number' => $request->movement_number,
            'curiosity' => $request->curiosity,
            'tips' => $request->tips ? serialize($request->tips) : null,
            'youtube' => $request->youtube ? serialize($request->youtube) : null,
            'itunes' => $request->itunes ? serialize($request->itunes) : null,
            'key' => $request->key,
            'score_editor' => $request->score_editor,
            'score_publisher' => $request->score_publisher,
            'score_copyright' => $request->score_copyright,
            'composer_id' => $request->composer_id,
            'audio_path' => Piece::upload($request, 'audio_path','audio'),
            'score_path' => Piece::upload($request, 'score_path','score'),
            'audio_path_rh' => Piece::upload($request, 'audio_path_rh','audio_rh'),
            'audio_path_lh' => Piece::upload($request, 'audio_path_lh','audio_lh'),
            'creator_id' => auth()->user()->id,
            'views' => mt_rand(5,15),
        ]);

        $piece->tags()->attach(array_merge($request->tags ?? [], $request->level ?? [], $request->length ?? [], $request->period ?? []));

        return redirect(route('piano-lit.pieces.index'))->with('success', "The piece has been successfully added!");
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Piece  $piece
     * @return \Illuminate\Http\Response
     */
    public function show(Piece $piece)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Piece  $piece
     * @return \Illuminate\Http\Response
     */
    public function edit(Piece $piece)
    {
        $composers = Composer::orderBy('name')->get();
        $types = Tag::byTypes($except = ['levels', 'periods', 'lengths']);

        return view('projects/pianolit/pieces/edit', compact(['composers', 'piece', 'types']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Piece  $piece
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Piece $piece)
    {
        $this->authorize('update', $piece);

        $request->validate([
            'name' => 'required|max:255',
            'key' => 'required',
            'composer_id' => 'required',
            'period' => 'required',
            'key' => 'required',
            'length' => 'required',
            'level' => 'required',
        ]);

        $piece->name = $request->name;
        $piece->nickname = $request->nickname;
        $piece->collection_name = $request->collection_name;
        $piece->collection_number = $request->collection_number;
        $piece->catalogue_name = $request->catalogue_name;
        $piece->catalogue_number = $request->catalogue_number;
        $piece->movement_number = $request->movement_number;
        $piece->curiosity = $request->curiosity;
        $piece->tips = $request->tips ? serialize($request->tips) : null;
        $piece->youtube = $request->youtube ? serialize($request->youtube) : null;
        $piece->itunes = $request->itunes ? serialize($request->itunes) : null;
        $piece->key = $request->key;
        $piece->score_editor = $request->score_editor;
        $piece->score_publisher = $request->score_publisher;
        $piece->score_copyright = $request->score_copyright;
        $piece->composer_id = $request->composer_id;
      
        $piece->save();

        $piece->tags()->sync(array_merge($request->tags ?? [], $request->level ?? [], $request->length ?? [], $request->period ?? []));

        if ($request->file('audio_path')) {
            Piece::remove([$piece->audio_path]);
            $piece->audio_path = Piece::upload($request, 'audio_path','audio');
            $piece->save();
        }

        if ($request->file('score_path')) {
            Piece::remove([$piece->score_path]);
            $piece->score_path = Piece::upload($request, 'score_path','score');
            $piece->save();
        }
        
        if ($request->file('audio_path_rh')) {
            Piece::remove([$piece->audio_path_rh]);
            $piece->audio_path_rh = Piece::upload($request, 'audio_path_rh','audio_rh');
            $piece->save();
        }
        
        if ($request->file('audio_path_lh')) {
            Piece::remove([$piece->audio_path_lh]);
            $piece->audio_path_lh = Piece::upload($request, 'audio_path_lh','audio_lh');
            $piece->save();
        }

        return redirect()->back()->with('success', "The piece has been successfully updated!");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Piece  $piece
     * @return \Illuminate\Http\Response
     */
    public function destroy(Piece $piece)
    {
        $this->authorize('update', $piece);
        
        Piece::remove([$piece->audio_path, $piece->audio_path_rh, $piece->audio_path_lh, $piece->score_path]);
        $piece->tags()->detach();
        $piece->delete();

        return redirect()->back()->with('success', "The piece has been successfully deleted!");
    }
}
