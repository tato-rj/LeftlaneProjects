<?php

namespace App\VideoProcessor;

class Thumbnail
{
	public function create(VideoProcessor $processor)
	{
		$file = $processor->rawFile();
		$duration = $file->getDurationInSeconds() / 2;

        $processor->rawFile()
        		  ->getFrameFromSeconds((int) $duration)
        		  ->export()
        		  ->toDisk('gcs')
        		  ->save(
        		  	$processor->path()->thumbnail()
        		  );
	}
}