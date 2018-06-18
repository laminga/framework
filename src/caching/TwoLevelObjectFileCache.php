<?php

namespace minga\framework\caching;

use minga\framework\Profiling;
use minga\framework\Serializator;

class TwoLevelObjectFileCache
{
	private $cache;

	public function __construct($path)
	{
		$this->cache = new TwoLevelStringFileCache($path);
	}
	public function Clear($key1 = null, $key2 = null)
	{
		$this->cache->Clear($key1, $key2 = null);
	}
	public function HasData($key1, $key2, & $out = null)
	{
		$stringValue = null;
		if ($this->cache->HasData($key1, $key2, $stringValue))
		{
			Profiling::BeginTimer();
			$out = Serializator::Deserialize($stringValue);
			Profiling::EndTimer();
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

	public function PutData($key1, $key2, $value)
	{
		$this->cache->PutData($key1, $key2, Serializator::Serialize($value));
	}
}



