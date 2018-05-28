<?php

namespace minga\framework\caching;

class StringFileCache
{
	private $cache;

	public function __construct($path)
	{
		$this->cache = new TwoLevelStringFileCache($path);  
	}

	public function HasData($key, & $out = null)
	{
		if ($this->cache->HasData($key, null, $out))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	public function PutDataIfMissing($key, $value)
	{
		if ($this->HasData($key)) return;
		$this->PutData($key, $value);
	}

	public function PutData($key, $value)
	{
		$this->cache->PutData($key, null, $value);
	}
}



