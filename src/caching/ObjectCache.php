<?php

namespace minga\framework\caching;

use minga\framework\Serializator;
use minga\framework\Context;

class ObjectCache
{
	private $cache;

	public function __construct($path)
	{
		$this->cache = new StringCache($path);
	}

	public function Clear($key = null)
	{
		$this->cache->Clear($key);
	}

	public function HasData($key, & $out = null)
	{
		$stringValue = null;
		if ($this->cache->HasData($key, $stringValue))
		{
			$out = Serializator::Deserialize($stringValue);
			return true;
		}
		else
		{
			$out = null;
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
		$this->cache->PutData($key, Serializator::Serialize($value));
	}
}


