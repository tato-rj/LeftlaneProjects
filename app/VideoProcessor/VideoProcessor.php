<?php

namespace App\VideoProcessor;

use App\Projects\VideoUploader\Video;

class VideoProcessor
{
	protected $raw_file;
	public $temp_path, $filename, $withThumbnail, $originalDimensions;

	public function __construct(Video $video)
	{
		ini_set('max_execution_time', 9000);
		$this->video = $video;
		$this->filename = basename($video->temp_path);
		$this->withThumbnail = false;
		$this->raw_file = \FFMpeg::fromDisk('public')->open($this->video->temp_path);
		
		$this->getOriginalDimensions();
	}

	public function rawFile()
	{
		return $this->raw_file;
	}

	public function getOriginalDimensions()
	{
		$copy = $this->raw_file;

		$this->originalDimensions = $copy->getVideoStream()->getDimensions();
	}

	public function path()
	{
		return new Path($this);
	}

	public function run()
	{
		(new Compressor)->fire($this);

		if ($this->withThumbnail)
			(new Thumbnail)->create($this);

		$this->cleanup();

		return $this;
	}

	public function withThumbnail()
	{
		$this->withThumbnail = true;

        return $this;
	}

	public function cleanup()
	{
		\Storage::disk('public')->delete($this->video->temp_path);

		$this->video->update(['temp_path' => null]);
		
		\FFMpeg::cleanupTemporaryFiles();
	}
}