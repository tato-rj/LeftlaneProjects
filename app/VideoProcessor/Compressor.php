<?php

namespace App\VideoProcessor;

class Compressor
{
	public function fire(VideoProcessor $processor)
	{
		$dimensions = (new Dimensions($processor))->get();

		$processor->rawFile()
				  // ->resize($dimensions->width, $dimensions->height)
				  ->export()
				  ->toDisk('gcs')
				  ->inFormat((new Format)->getLowQuality())
				  ->save($processor->path()->video());
	}
}