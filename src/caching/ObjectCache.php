<?php

namespace minga\framework\caching;

use minga\framework\Context;
use minga\framework\Serializator;
use minga\framework\Profiling;

class ObjectCache
{
	private $cache;

	public function __construct($path, $forceFileSystem = false)
	{
		$this->cache = new StringCache($path, $forceFileSystem);
	}

	public function Clear($key = null)
	{
		$this->cache->Clear($key);
	}

	public function HasData($key, & $out = null)
	{
		Profiling::BeginTimer();
		$stringValue = null;
		if ($this->cache->HasData($key, $stringValue))
		{
			$out = Serializator::Deserialize($stringValue);
			Profiling::EndTimer();
			return true;
		}
		else
		{
			$out = null;
			Profiling::EndTimer();
			return false;
		}
	}
	public function PutDataIfMissing($key, $value)
	{
		if ($this->HasData($key))
			return;
		$this->PutData($key, $value);
	}

	public function PutData($key, $value)
	{
		$this->cache->PutData($key, Serializator::Serialize($value));
	}
}



