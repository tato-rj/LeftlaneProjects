<?php

namespace App\Projects\PianoLit;

use Carbon\Carbon;
use App\Projects\PianoLit\{Piece, Composer, Tag, Api, Country, Playlist};

class Api
{
    public function composers()
    {
        return Composer::with('pieces')->get();
    }

    public function periods()
    {
        return Tag::periods()->with('pieces')->get();
    }

    public function improve()
    {
        return Tag::improve()->with('pieces')->get();
    }

    public function countries()
    {
        return Country::with('pieces')->get();
    }

    public function levels()
    {
        return Tag::levels()->with('pieces')->get();
    }

    public function foundation()
    {
        return Playlist::foundation()->with('pieces')->get();
    }

    public function trending()
    {
        return Piece::orderBy('views', 'DESC')->take(10)->get();
    }

    public function latest()
    {
        return Piece::latest()->take(15)->get();
    }

    public function famous()
    {
        return Piece::famous()->take(10);
    }

    public function flashy()
    {
        return Piece::flashy()->take(10);
    }

    public function ornaments()
    {
        return Piece::ornaments()->take(10);
    }

	public function setCustomAttributes($model)
	{
        $classname = get_class($model);

        if ($classname == 'App\Projects\PianoLit\Piece') {

            $model->setAttribute('tips_array', $model->tips);
            $model->setAttribute('youtube_array', $model->youtube);
            $model->setAttribute('level', $model->level_name);
            $model->setAttribute('period', $model->period()->name);
            $model->setAttribute('itunes_array', $model->itunes);
            $model->setAttribute('catalogue', $model->catalogue);
            $model->setAttribute('collection', $model->collection);
            $model->setAttribute('tags_array', $model->tags_array);
            $model->setAttribute('short_name', $model->short_name);
            $model->setAttribute('medium_name', $model->medium_name);
            $model->setAttribute('long_name', $model->long_name);
            $model->setAttribute('audio', $model->file_path('audio_path'));
            $model->setAttribute('audio_rh', $model->file_path('audio_path_rh'));
            $model->setAttribute('audio_lh', $model->file_path('audio_path_lh'));
            $model->setAttribute('score', $model->file_path('score_path'));
            $model->composer->setAttribute('alive_on', $model->composer->alive_on);
            $model->composer->setAttribute('short_name', $model->composer->short_name);
            $model->composer->setAttribute('born_at', $model->composer->born_at);
            $model->composer->setAttribute('died_at', $model->composer->died_at);
        
        } else if ($classname == 'App\Projects\PianoLit\Composer') {
        
            $model->setAttribute('alive_on', $model->alive_on);
            $model->setAttribute('born_at', $model->born_at);
            $model->setAttribute('died_at', $model->died_at);
            $model->setAttribute('short_name', $model->short_name);
        
        }
	}

    public function prepare($request, $pieces, $inputArray)
    {
        // If the request came from the input...
        if ($request->has('global')) {
            // ...look into each result to see if it contains all the words on the input
            $pieces->each(function($piece, $key) use ($inputArray, $pieces) {
                // Selects only the relevant rows for the search
                $pieceArray = array_values($piece->only([
                    'name',
                    'nickname',
                    'catalogue_name',
                    'catalogue_number',
                    'collection_name',
                    'collection_number',
                    'key']));
                // Bring every word to lower case
                $pieceArray = array_map('mb_strtolower', $pieceArray);
                // Prepare the tags array
                $pieceTags = $piece->tags()->pluck('name');
                // Prepares the composer name
                $pieceComposer = mb_strtolower($piece->composer()->pluck('name')->first());

                // Merges piece relevant fields with tags and composer name
                $pieceArray = $pieceTags->merge($pieceArray);
                $pieceArray->push($pieceComposer);

                $matchesCount = 0;

                foreach ($pieceArray as $pieceTag) {
                    foreach ($inputArray as $inputTag) {
                        if (is_numeric($inputTag)) {
                            if ($pieceTag == $inputTag)
                                $matchesCount++;
                        } else {
                            if (strpos($pieceTag, $inputTag) !== false)
                                $matchesCount++;
                        }
                    }
                }

                if ($matchesCount != count($inputArray))
                    $pieces->forget($key);
            });
        }
        
        $pieces->each(function($result) {
            self::setCustomAttributes($result);
        });
    }

    public function prepareInput($request)
    {
        $inputString = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $request->search)));

        $inputArray = array_map('mb_strtolower', explode(' ', $inputString));

        foreach ($inputArray as $key => $tag) {

            $inputArray = $this->fixException($inputArray, $key, $tag, ['left', 'right', 'crossing'], 'hand');
            $inputArray = $this->fixException($inputArray, $key, $tag, ['broken', 'block'], 'chords');
            $inputArray = $this->fixException($inputArray, $key, $tag, ['alberti'], 'bass');
            $inputArray = $this->fixException($inputArray, $key, $tag, ['finger'], 'substitution');

        }

        return $inputArray;  
    }

    public function fixException($array, $key, $tag, $exceptions, $append)
    {
        if (in_array($tag, $exceptions)) {
            unset($array[$key], $array[$key+1]);
            array_push($array, "{$tag} {$append}");
        }

        return $array;
    }
}
