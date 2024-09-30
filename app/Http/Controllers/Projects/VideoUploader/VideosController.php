<?php

namespace App\Http\Controllers\Projects\VideoUploader;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Jobs\{ProcessVideo, RotateVideo};
use App\Projects\VideoUploader\Video;
use Illuminate\Support\Facades\Validator;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;

class VideosController extends Controller
{
    public function json(Video $video)
    {
        return $video;    
    }

    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required',
            'email' => 'required|email',
            'name' => 'required',
            'composer' => 'required',
            'user_id' => 'required|integer',
            'piece_id' => 'required|integer',
            'origin' => 'required|string'
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json($validator->errors(), 422);
            } else {
                return back()->withErrors($validator)->withInput();
            }
        }

        $receiver = new FileReceiver('file', $request, HandlerFactory::classFromRequest($request));

        if (! $receiver->isUploaded()) {
            \Log::debug('File not uploaded');
        }

        $fileReceived = $receiver->receive();

        if ($fileReceived->isFinished()) {
            \Log::debug('File completed');
            
            $file = $fileReceived->getFile();

            $filename = $request->name . '.' . 'mp4';

            $path = \Storage::disk('public')->putFileAs(str_slug($request->composer), $file, $filename);

            $video = Video::create([
                // 'origin' => $request->origin,
                'piece_id' => $request->piece_id,
                // 'user_id' => $request->user_id,
                // 'user_email' => $request->email,
                'notes' => $request->notes,
                // 'temp_path' => $path,
                'video_path' => $path,
                'original_size' => $file->getSize(),
            ]);

            $video->markAsCompleted();
            // ProcessVideo::dispatch(
            //     Video::temporary($file, $request->toArray())
            // );

            unlink($file->getPathname());

            return response(200);
        }

        return [
            'done' => $fileReceived->handler()->getPercentageDone(),
            'status' => true
        ];
    }

    public function fix(Request $request)
    {
        return Video::latest()->take(5)->get();
        // $video = Video::where('video_path', $request->old_path)->first();

        // if (is_null($video)) {
        //     return response()->json([
        //         'message' => 'Video path not found: ' . $request->old_path
        //     ], 404);
        // }

        // if ($video->video_path != $request->new_path) {
        //     \Storage::disk('public')->move($video->video_path, $request->new_path);

        //     $video->update(['video_path' => $request->new_path]);

        //     return response()->json([
        //         'message' => 'Video path updated to ' . $request->new_path
        //     ], 200);
        // }

        // return response()->json([
        //     'message' => 'Nothing to change'
        // ], 200);
    }

    public function update(Request $request, Video $video)
    {
        if (\Storage::disk('public')->exists($video->video_path))
            \Storage::disk('public')->move($video->video_path, $request->video_path);
        
        $video->update(['video_path' => $request->video_path]);

        return back()->with('success', 'The video has been updated');
    }

    public function rotate(Video $video)
    {
        RotateVideo::dispatch($video);

        return back()->with('success', 'The video is being rotated');
    }

    public function destroy(Request $request, Video $video)
    {
        if ($video->video_path && \Storage::disk('public')->exists($video->video_path))
            \Storage::disk('public')->delete($video->video_path);

        $video->delete();

        return back()->with('success', 'The video was successfully deleted');
    }
}
