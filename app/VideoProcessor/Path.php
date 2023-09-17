<?php

namespace App\VideoProcessor;

class Path
{
	protected $ext = 'mp4';
	protected $namespace = 'performances';

	public function __construct(VideoProcessor $processor)
	{
		$this->processor = $processor;
	}

	public function thumbnail()
	{
		$path = str_replace(pathinfo($this->processor->filename, PATHINFO_EXTENSION), 'jpg', $this->processor->filename);

		return $this->namespace . '/test@email.com/' . $path;
	}

	public function video()
	{
		return $this->namespace . '/test@email.com/' . $this->newFilename($this->processor->filename);
	}

	function newFilename($filename) {
	    $info = pathinfo($filename);

	    return $info['filename'] . '.' . $this->ext;
	}
}