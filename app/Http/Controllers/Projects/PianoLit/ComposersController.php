<?php

namespace App\Http\Controllers\Projects\PianoLit;

use App\Projects\PianoLit\{Composer, Country};
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ComposersController extends Controller
{
    public function app()
    {
        return Composer::with('pieces')->get();
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $filters = ['name', 'date_of_birth', 'pieces_count'];
        
        $sort = ['name', 'asc'];

        if (request()->has('sort') && in_array(request('sort'), $filters))
            $sort[0] = request('sort');

        if (request()->has('order') && in_array(request('order'), ['asc', 'desc']))
            $sort[1] = request('order');

        $countries = Country::all();
        $composers = Composer::orderBy($sort[0], $sort[1])->paginate(20);

        return view('projects/pianolit/composers/index', compact(['composers', 'countries']));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
            'name' => 'required|unique:pianolit.composers|max:140',
            'biography' => 'required',
            'country_id' => 'required',
            'period' => 'required',
            'date_of_birth' => 'required',
            'date_of_death' => 'required',
        ]);

        $author = Composer::create([
            'name' => $request->name,
            'biography' => $request->biography,
            'curiosity' => $request->curiosity,
            'country_id' => $request->country_id,
            'period' => $request->period,
            'date_of_birth' => Carbon::parse($request->date_of_birth)->format('Y-m-d'),
            'date_of_death' => Carbon::parse($request->date_of_death)->format('Y-m-d'),
            'creator_id' => auth()->user()->id
        ]);

        return redirect()->back()->with('success', "$request->name has been successfully added!");
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Composer  $composer
     * @return \Illuminate\Http\Response
     */
    public function show(Composer $composer)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Composer  $composer
     * @return \Illuminate\Http\Response
     */
    public function edit(Composer $composer)
    {
        $countries = Country::all();
        $pieces = $composer->pieces;
        
        return view('projects/pianolit/composers/edit', compact(['composer', 'pieces', 'countries']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Composer  $composer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Composer $composer)
    {
        $this->authorize('update', $composer);

        $request->validate([
            'name' => 'required|max:140',
            'biography' => 'required'
        ]);

        $composer->update([
            'name' => $request->name,
            'date_of_birth' => Carbon::parse($request->date_of_birth)->format('Y-m-d'),
            'date_of_death' => Carbon::parse($request->date_of_death)->format('Y-m-d'),
            'biography' => $request->biography,
            'curiosity' => $request->curiosity,
            'country_id' => $request->country_id,
            'period' => $request->period
        ]);

        return redirect()->back()->with('success', "$request->name has been updated");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Composer  $composer
     * @return \Illuminate\Http\Response
     */
    public function destroy(Composer $composer)
    {
        $this->authorize('update', $composer);

        if ($composer->pieces()->exists())
            $composer->pieces->each->delete();
    
        $composer->delete();

        return redirect()->back()->with('success', "$composer->name has been successfully deleted!");
    }
}
