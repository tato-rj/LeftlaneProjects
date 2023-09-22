<?php

namespace App\VideoProcessor;

class Dimensions
{
	protected $fixedWidth = 640;
	public $width, $height;

	public function __construct(VideoProcessor $processor)
	{
		$this->processor = $processor;
	}

	public function get()
	{
		$originalH = $this->processor->originalDimensions->getHeight();
		$originalW = $this->processor->originalDimensions->getWidth();

		if ($originalW > $this->fixedWidth) {
			$this->width =  $this->fixedWidth;
			$this->height = (int) ($this->width * ($originalH / $originalW));
		} else {
			$this->width = $originalW;
			$this->height = $originalH;
		}

		$this->processor->video->update([
			'compressed_dimensions' => $this->width . 'x' . $this->height,
			'original_dimensions' => $originalW . 'x' . $originalH
		]);

		return $this;
	}
}