<?php

namespace App\VideoProcessor;

class Dimensions
{
	protected $fixedHeight = 360;

	public function __construct(VideoProcessor $processor)
	{
		$this->processor = $processor;
	}

	public function get()
	{
		$originalH = $this->processor->originalDimensions->getHeight();
		$originalW = $this->processor->originalDimensions->getWidth();

		$this->width =  (int) round($this->fixedHeight / ($originalH/$originalW));
		$this->height = $this->fixedHeight;

		return $this;
	}
}