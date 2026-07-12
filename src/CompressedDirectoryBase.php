<?php

namespace minga\framework;

class CompressedDirectoryBase
{
	public string $path;
	public string $expandedPath = '';
	protected string $file;
	protected bool $expanded = false;

	public function __construct(string $path, string $file = 'content.zip')
	{
		$this->path = $path;
		$this->file = $file;
	}

	public function Release() : void
	{
		if ($this->expanded == false)
			return;
		IO::RemoveDirectory($this->expandedPath);
		$this->expanded = false;
	}

	public function GetFilename() : string
	{
		throw new \Exception('Not implemented.');
	}

	public function IsCompressed() : bool
	{
		throw new \Exception('Not implemented.');
	}

	public function Compress() : bool
	{
		throw new \Exception('Not implemented.');
	}

	public function Expand() : void
	{
		throw new \Exception('Not implemented.');
	}
}
