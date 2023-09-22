<?php

namespace App\VideoProcessor;

class Path
{
	protected $ext = 'mp4';
	protected $namespace = 'performances';
	protected $rename = false;

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

	public function rename()
	{
		$this->rename = true;

		return $this;
	}

	function newFilename($filename) {
	    $info = pathinfo($filename);

	    if ($this->rename)
	    	return str_shuffle($info['filename']) . '.' . $this->ext;

	    return $info['filename'] . '.' . $this->ext;
	}
}