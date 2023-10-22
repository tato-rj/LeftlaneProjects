<?php

namespace App\SystemFiles;

class SystemFiles
{
	protected $files, $junk, $command;

	public function __construct()
	{
		$this->root = \Storage::disk('public');
	}

	public function chunks()
	{
		$collection = $this->makeCollection('chunks');

		$this->files = $this->getFiles($collection);

		$this->junk = $this->getJunk($collection);

		$this->command = 'chunks:clear';

		return $this;
	}

	public function temporary()
	{
		$collection = $this->makeCollection('temporary');
		
		$this->files = $this->getFiles($collection);

		$this->junk = $this->getJunk($collection);

		$this->command = 'temporary:clear';

		return $this;
	}

	public function isEmpty()
	{
		return $this->files()->isEmpty() && $this->junk()->isEmpty();
	}

	public function command()
	{
		return $this->command;
	}

	public function files()
	{
		return $this->files;
	}

	public function junk()
	{
		return $this->junk;
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

	public function getJunk($collection)
	{
		return $collection->filter(function($file) {
			return $file['last_modified']->lt(now()->addHours(2));
		});
	}

	public function getFiles($collection)
	{
		return $collection->filter(function($file) {
			return $file['last_modified']->gt(now()->addHours(2));
		});
	}
}