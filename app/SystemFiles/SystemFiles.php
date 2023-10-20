<?php

namespace App\SystemFiles;

class SystemFiles
{
	protected $files, $command;

	public function __construct()
	{
		$this->root = \Storage::disk('public');
	}

	public function chunks()
	{
		$this->files = $this->makeCollection('chunks');
		$this->command = 'chunks:clear';

		return $this;
	}

	public function temporary()
	{
		$this->files = $this->makeCollection('temporary');
		$this->command = 'temporary:clear';

		return $this;
	}

	public function command()
	{
		return $this->command;
	}

	public function files()
	{
		return $this->files;
	}

	public function makeCollection($folder)
	{
		$collection = collect();

		foreach ($this->root->files($folder) as $file) {
			$collection->push([
				'name' => basename($file),
				'path' => $file,
				'size' => $this->root->size($file),
				'last_modified' => carbon($this->root->lastModified($file))
			]);
		}

		return $collection;
	}
}