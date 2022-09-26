<?php

namespace minga\framework\caching;

use minga\framework\Context;
use minga\framework\IO;
use minga\framework\settings\CacheSettings;

class FileFileCache
{
	private string $path;

	public function __construct(string $path)
	{
		$this->path = Context::Paths()->GetStorageCaches() . '/services/' . $path;
	}

	public function Clear($key1 = null) : void
	{
		if ($key1 == null)
		{
			IO::ClearDirectory($this->path, true);
			return;
		}
		$key1 = (string)$key1;

		$file = $this->ResolveFilename($key1, false);
		IO::Delete($file);
	}

	public function HasData($key1, &$out = null, bool $overriteTwoState = false) : bool
	{
		if (Context::Settings()->Cache()->Enabled !== CacheSettings::Enabled && $overriteTwoState == false)
			return false;

		$file = $this->ResolveFilename($key1);

		if (file_exists($file))
		{
			$out = $file;
			return true;
		}
		$out = null;
		return false;
	}

	public function PutDataIfMissing($key1, $value) : void
	{
		if ($this->HasData($key1))
			return;
		$this->PutData($key1, $value);
	}

	public function PutData($key1, $filename)
	{
		if (Context::Settings()->Cache()->Enabled === CacheSettings::Disabled)
			return;

		$file = $this->ResolveFilename($key1, true);
		copy($filename, $file);
		return $file;
	}

	private function ResolveFilename($key1, bool $create = false)
	{
		$folder = $this->path;
		if($create)
			IO::EnsureExists($folder);
		return $folder . "/" . $key1 . ".dat";
	}
}
