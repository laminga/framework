<?php

namespace minga\framework\caching;

use minga\framework\settings\CacheSettings;
use minga\framework\Context;
use minga\framework\IO;

class TwoLevelFileFileCache
{
	private $path;

	public function __construct($path)
	{
		$this->path = Context::Paths()->GetStorageCaches() . '/services/' . $path;
	}

	public function HasData($key1, $key2, &$out = null, $overriteTwoState = false)
	{
	 if (Context::Settings()->Cache()->Enabled !== CacheSettings::Enabled && $overriteTwoState == false)
			return false;

		$file = $this->resolveFilename($key1, $key2);
		if (file_exists($file))
		{
			$out = $file;
			return true;
		}
		else
		{
			$out = null;
			return false;
		}
	}
	public function PutDataIfMissing($key1, $key2, $value)
	{
		if ($this->HasData($key1, $key2)) return;
		$this->PutData($key1, $key2, $value);
	}

	public function PutData($key1, $key2, $filename)
	{
		if (Context::Settings()->Cache()->Enabled === CacheSettings::Disabled)
			return;

		$file = $this->resolveFilename($key1, $key2, true);
		copy($filename, $file);
		return $file;
	}

	private function resolveFilename($key1, $key2, $create = false)
	{
		$folder = $this->path . "/" . $key1;
		if($create)
			IO::EnsureExists($folder);
		return $folder . "/" . $key2 . ".dat";
	}

}



