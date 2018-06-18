<?php

namespace minga\framework\caching;

use minga\framework\Context;
use minga\framework\IO;
use minga\framework\Profiling;
use minga\framework\settings\CacheSettings;

class TwoLevelStringFileCache
{
	private $path;

	public function __construct($path)
	{
		$this->path = Context::Paths()->GetStorageCaches() . '/services/' . $path;
		IO::EnsureExists($this->path);
	}

	public function Clear($key1 = null, $key2 = null)
	{
		if ($key1 == null)
		{
			IO::ClearDirectory($this->path);
			return;
		}
		$key1 = $this->keyToString($key1);
		$key2 = $this->keyToString($key2);
		
		$folder = $this->path . "/" . $key1;
		if ($key2 === null)
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
		$file = $this->resolveFilename($key1, $key2, false);
		if (file_exists($file))
			IO::Delete(file);
	}

	public function HasData($key1, $key2, &$value = null)
	{
		if (Context::Settings()->Cache()->Enabled !== CacheSettings::Enabled)
		{
			return false;
		}
		$file = $this->resolveFilename($key1, $key2);
		if (file_exists($file))
		{
			Profiling::BeginTimer();
			$value = IO::ReadAllText($file);
			touch($file);
			Profiling::EndTimer();
			return true;
		}
		else
			return false;
	}

	private function resolveFilename($key1, $key2, $create = false)
	{
		$key1 = $this->keyToString($key1);
		$key2 = $this->keyToString($key2);
		if ($key2 != null)
		{
			$folder = $this->path . "/" . $key1;
			if($create)
				IO::EnsureExists($folder);
			return $folder . "/" . $key2 . ".txt";
		}
		else
			return $this->path . "/" . $key1 . ".txt";
	}

	public function PutDataIfMissing($key1, $key2, $value)
	{
		if (Context::Settings()->Cache()->Enabled === CacheSettings::Disabled || $this->HasData($key1, $key2))
			return;
		$this->PutData($key1, $key2, $value);
	}

	public function PutData($key1, $key2, $value)
	{
		if (Context::Settings()->Cache()->Enabled === CacheSettings::Disabled)
			return;

		$file = $this->resolveFilename($key1, $key2, true);
		IO::WriteAllText($file, $value);
	}

	private function keyToString($key)
	{
		if ($key == null)
			return "";
		else
			return $key . "";
	}

}

