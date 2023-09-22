<?php

namespace App\VideoProcessor;

class Orientation
{
	protected $dimensions;

	public function __construct(VideoProcessor $processor)
	{
		$this->processor = $processor;
		$this->originalDimensions = explode('x', $this->processor->video->original_dimensions);
		$this->compressedDimensions = explode('x', $this->processor->video->compressed_dimensions);
	}

	public function rotate()
	{
		$newPath = $this->processor->path()->rename()->video();

		$this->processor
			 ->rawFile()
			 ->resize($this->compressedDimensions[1], $this->compressedDimensions[0])
			 ->export()
			 ->toDisk('gcs')
			 ->inFormat((new Format)->getLowQuality())
			 ->save($newPath);

		$this->updateVideo($newPath);
	}

	public function updateVideo($newPath)
	{
		\Storage::disk('gcs')->delete($this->processor->video->video_path);

		$this->processor->video->update([
			'video_path' => $newPath,
			'original_dimensions' => $this->originalDimensions[1] . 'x' . $this->originalDimensions[0],
			'compressed_dimensions' => $this->compressedDimensions[1] . 'x' . $this->compressedDimensions[0],
		]);

		$this->processor->video->sendJobProcessedNotification();
	}
}