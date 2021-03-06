<?php

namespace App\Http\Controllers\Projects\PianoLit;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Projects\PianoLit\{Composer, Piece, Tag, Admin};

class EditorsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('create');

        $editors = Admin::editors()->paginate(10);
        return view('projects/pianolit/editors/index', compact('editors'));
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
            'name' => 'required|string|min:4',
            'email' => 'required|email|unique:pianolit.admins',
            'password' => 'string|confirmed|min:4'
        ]);

        Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => 'editor',
            'password' => \Hash::make($request->password)
        ]);

        return redirect(route('piano-lit.editors.index'))->with('success', "{$request->name} has been successfully added as an editor!");
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Admin $editor)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Admin $editor)
    {
        $pieces = $editor->pieces;
        
        return view('projects/pianolit/editors/edit', compact(['editor', 'pieces']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Admin $editor)
    {
        $this->authorize('update', $editor);

        if (isset($request->password)) {
            $request->validate(['password' => 'string|confirmed|min:4']);
            
            $editor->update(['password' => \Hash::make($request->password)]);
            
            $feedback = 'The password has been successfully updated!';
        } else {
            $request->validate([
                'name' => 'required|string|min:4',
                'email' => 'required|email',
            ]);

            $editor->update([
                'name' => $request->name,
                'email' => $request->email,
            ]);

            $feedback = "{$request->name}'s profile has been updated!";
        }



        return redirect()->back()->with('success', $feedback);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Admin $editor)
    {
        $this->authorize('update', $editor);

        if ($editor->pieces()->exists()) {
            $editor->pieces->each(function($piece) {
                $piece->creator_id = null;
                $piece->save();
            });
        }
        
        $editor->delete();

        return redirect()->back()->with('success', "$editor->name has been successfully deleted!");
    }
}
