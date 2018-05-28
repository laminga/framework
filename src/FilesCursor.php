<?php

namespace minga\framework;

class FilesCursor
{
	private $path;
	private $ext;
	private $handle = null;

	public $Current;

	public function __construct($path, $ext = "")
	{
		$this->path = $path;
		$this->ext = $ext;
	}
	public function Close()
	{
		if ($this->handle != null)
		{
			closedir($this->handle);
			$this->handle = null;
		}
	}
	public function GetNext()
	{
		if ($this->handle == null)
		{
			if (($this->handle = opendir($this->path)) === false)
				throw new \Exception('Invalid directory.');
		}
		while(true)
		{
			if (false === ($entry = readdir($this->handle)))
			{
				$this->Close();
				return false;
			}
			if (($this->ext == '' || Str::EndsWith($entry, $this->ext)) &&
				$entry != '..' && $entry != '.' && is_file($this->path . '/'. $entry))
			{
				$this->Current = $entry;
				return true;
			}
		}
	}
}

