<?php

namespace minga\framework\caching;

use minga\framework\Context;
use minga\framework\IO;
use minga\framework\settings\CacheSettings;

class TwoLevelFileFileCache
{
	private $path;

	public function __construct($path)
	{
		$this->path = Context::Paths()->GetStorageCaches() . '/services/' . $path;
	}

	public function Clear($key1 = null, $key2 = null) : void
	{
		if ($key1 == null)
		{
			IO::ClearDirectory($this->path, true);
			return;
		}
		$key1 = (string)$key1;
		$key2 = (string)$key2;

		$folder = $this->path . "/" . $key1;
		if ($key2 === '')
		{
			if (file_exists($folder))
			{
				if (is_dir($folder))
					IO::RemoveDirectory($folder);
				else
					IO::Delete($folder);
			}
			return;
		}
		$file = $this->ResolveFilename($key1, $key2, false);
		IO::Delete($file);
	}

	public function HasData($key1, $key2, &$out = null, bool $overriteTwoState = false) : bool
	{
		if (Context::Settings()->Cache()->Enabled !== CacheSettings::Enabled && $overriteTwoState == false)
			return false;

		$file = $this->ResolveFilename($key1, $key2);

		if (file_exists($file))
		{
			$out = $file;
			return true;
		}
		$out = null;
		return false;
	}

	public function PutDataIfMissing($key1, $key2, $value) : void
	{
		if ($this->HasData($key1, $key2))
			return;
		$this->PutData($key1, $key2, $value);
	}

	public function PutData($key1, $key2, $filename) : string
	{
		if (Context::Settings()->Cache()->Enabled === CacheSettings::Disabled)
			return '';

		$file = $this->ResolveFilename($key1, $key2, true);
		copy($filename, $file);
		return $file;
	}

	private function ResolveFilename($key1, $key2, bool $create = false) : string
	{
		$folder = $this->path . "/" . $key1;
		if($create)
			IO::EnsureExists($folder);
		return $folder . "/" . $key2 . ".dat";
	}
}



