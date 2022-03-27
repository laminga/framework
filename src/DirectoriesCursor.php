<?php

namespace minga\framework;

class DirectoriesCursor
{
	private string $path;
	private string $ext;
	private $handle = null;

	public $Current;

	public function __construct(string $path, string $ext = "")
	{
		$this->path = $path;
		$this->ext = $ext;
	}

	public function Close() : void
	{
		if ($this->handle != null)
		{
			closedir($this->handle);
			$this->handle = null;
		}
	}

	public function GetNext() : bool
	{
		if ($this->handle == null)
		{
			if (($this->handle = IO::OpenDirNoWarning($this->path)) === false)
				throw new ErrorException('Directorio Inválido.');
		}
		while(true)
		{
			if (false === ($entry = readdir($this->handle)))
			{
				$this->Close();
				return false;
			}
			if (($this->ext == '' || Str::EndsWith($entry, $this->ext))
				&& $entry != '..' && $entry != '.' && is_dir($this->path . '/' . $entry))
			{
				$this->Current = $entry;
				return true;
			}
		}
	}
}

