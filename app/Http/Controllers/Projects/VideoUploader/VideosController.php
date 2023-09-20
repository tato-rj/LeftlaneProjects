<?php

namespace App\Http\Controllers\Projects\VideoUploader;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Jobs\ProcessVideo;
use App\Projects\VideoUploader\Video;
use Illuminate\Support\Facades\Validator;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;

class VideosController extends Controller
{
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required',
            'email' => 'required|email',
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

        \Log::debug('File received');

        $receiver = new FileReceiver('file', $request, HandlerFactory::classFromRequest($request));

        if (! $receiver->isUploaded()) {
            \Log::debug('File not uploaded');
        }

        $fileReceived = $receiver->receive();

        if ($fileReceived->isFinished()) {
            $file = $fileReceived->getFile();

            ProcessVideo::dispatch(
                Video::temporary($file, $request->toArray())
            );

            unlink($file->getPathname());

            return response(200);
        }

        return [
            'done' => $fileReceived->handler()->getPercentageDone(),
            'status' => true
        ];
    }

    public function destroy(Request $request)
    {
        $video = Video::where(['user_id' => $request->user_id, 'piece_id' => $request->piece_id])->firstOrFail();

        $video->sendVideoDeletedNotification();

        if ($video->temp_path && \Storage::disk('public')->exists($video->temp_path))
            \Storage::disk('public')->delete($video->temp_path);

        if ($video->video_path && \Storage::disk('gcs')->exists($video->video_path))
            \Storage::disk('gcs')->delete($video->video_path);

        if ($video->thumb_path && \Storage::disk('gcs')->exists($video->thumb_path))
            \Storage::disk('gcs')->delete($video->thumb_path);

        $video->delete();

        return $request->wantsJson() ? response(200) : back();
    }
}
