<?php

namespace minga\framework\caching;

use minga\framework\Context;
use minga\framework\IO;
use minga\framework\Profiling;
use minga\framework\settings\CacheSettings;

class BaseTwoLevelStringFileCache
{
	private $path;

	public function __construct($path)
	{
		$this->path = Context::Paths()->GetStorageCaches() . '/services/' . $path;
		IO::EnsureExists($this->path);
	}

	public function Clear($key1 = null, $key2 = null) : void
	{
		if ($key1 == null)
		{
			IO::ClearDirectory($this->path);
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
				return;
			}
		}
		$file = $this->ResolveFilename($key1, $key2, false);
		IO::Delete($file);
	}

	public function HasData($key1, $key2, &$value = null) : bool
	{
		if (Context::Settings()->Cache()->Enabled !== CacheSettings::Enabled)
		{
			return false;
		}
		$file = $this->ResolveFilename($key1, $key2);
		if (file_exists($file))
		{
			Profiling::BeginTimer();
			$value = IO::ReadAllText($file);
			touch($file);
			Profiling::EndTimer();
			return true;
		}

			return false;
	}

	private function ResolveFilename($key1, $key2, $create = false)
	{
		$key1 = (string)$key1;
		$key2 = (string)$key2;
		if ($key2 !== '')
		{
			$folder = $this->path . "/" . $key1;
			if($create)
				IO::EnsureExists($folder);
			return $folder . "/" . $key2 . ".txt";
		}

			return $this->path . "/" . $key1 . ".txt";
	}

	public function PutDataIfMissing($key1, $key2, $value) : void
	{
		if (Context::Settings()->Cache()->Enabled === CacheSettings::Disabled || $this->HasData($key1, $key2))
			return;
		$this->PutData($key1, $key2, $value);
	}

	public function PutData($key1, $key2, $value) : void
	{
		if (Context::Settings()->Cache()->Enabled === CacheSettings::Disabled)
			return;

		$file = $this->ResolveFilename($key1, $key2, true);
		IO::WriteAllText($file, $value);
	}
}

