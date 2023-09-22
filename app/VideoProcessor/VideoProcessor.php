<?php

namespace App\VideoProcessor;

use App\Projects\VideoUploader\Video;

class VideoProcessor
{
	protected $raw_file;
	public $temp_path, $filename, $withThumbnail, $originalDimensions, $compressedDimensions;

	public function __construct()
	{
		ini_set('max_execution_time', 9000);
		$this->withThumbnail = false;
	}

	public function uploaded(Video $video)
	{
		$this->video = $video;
		$this->filename = basename($video->temp_path);
		$this->raw_file = \FFMpeg::fromDisk('public')->open($this->video->temp_path);
		
		$this->getOriginalDimensions();

		return $this;
	}

	public function gcs(Video $video)
	{
		$this->video = $video;
		$this->filename = basename($this->video->video_path);
		$this->raw_file = \FFMpeg::fromDisk('gcs')->open($this->video->video_path);

		return $this;
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

	public function orientation()
	{
		return new Orientation($this);
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