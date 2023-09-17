<?php

namespace App\VideoProcessor;

class Thumbnail
{
	public function create(VideoProcessor $processor)
	{
        $processor->rawFile()
        		  ->getFrameFromSeconds(50)
        		  ->export()
        		  ->toDisk('gcs')
        		  ->save(
        		  	$processor->path()->thumbnail()
        		  );
	}
}