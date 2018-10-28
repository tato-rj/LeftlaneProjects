<?php

namespace App\Projects\PianoLit\Traits;

use App\Projects\PianoLit\Piece;

trait Resources
{
    public static function ageRange()
    {
        return ['under 13', '13 to 18', '18 to 25', '25 to 35', '35 to 45', '45 and up'];
    }

    public static function experience()
    {
        return ['none', 'little', 'a lot'];
    }

    public static function occupation()
    {
        return ['student', 'teacher', 'music lover'];
    }

    public static function preferredPieces()
    {
        return Piece::whereIn('id', [5, 13, 10, 12, 2, 8])->get();
    }

    public static function keys()
    {
        return ['C major', 'C minor', 'C# major', 'C# minor', 'Db major', 'Db minor', 'D major', 'D minor', 'D# major', 'D# minor', 'Eb major', 'Eb minor', 'E major', 'E minor', 'F major', 'F minor', 'F# major', 'F# minor', 'Gb major', 'Gb minor', 'G major', 'G minor', 'G# major', 'G# minor', 'Ab major', 'Ab minor', 'A major', 'A minor', 'A# major', 'A# minor', 'Bb major', 'Bb minor', 'B major', 'B minor'];
    }

    public static function catalogues()
    {
        $catalogues = ['Op.', 'KV', 'H', 'D', 'Hob', 'BWV', 'WoO', 'Op. posth.', 'Anh', 'Sz', 'L'];
        
        sort($catalogues);

        return $catalogues;
    }

    public static function periods()
    {
        return ['baroque', 'classical', 'romantic', 'impressionist', 'modern'];
    }

    public static function lengths()
    {
        return ['short', 'medium', 'long'];
    }

    public static function levels()
    {
        return ['beginner', 'intermediate', 'advanced'];
    }

	public static function upload($request, $filename, $folder)
	{
	    if ($request->file($filename)) {
	        return $request->file($filename)->storeAs(
                "public/pianolit/{$folder}", 
                md5($request->name . microtime()).'.'.$request->file($filename)->getClientOriginalExtension());
	    }
	}

	public static function remove($paths)
	{
		foreach ($paths as $path) {
			\Illuminate\Support\Facades\Storage::disk('local')->delete($path);
		}

		return true;
	}

	public static function lookup($file)
	{
		return $file ? 'text-success' : 'text-muted';
	}

    public function file_path($filename)
    {
        if (! $this->$filename)
            return null;

        $path = str_replace('public', 'storage', $this->$filename);
        return secure_asset($path);
    }
}