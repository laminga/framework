<?php

namespace minga\framework\caching;

use minga\framework\Context;

class TwoLevelStringCache
{
	private $cache;

	public function __construct($path)
	{
		$this->cache = Context::Settings()->Cache()->CreateFileCache($path);
	}
	public function Clear($key1 = null, $key2 = null)
	{
		$this->cache->Clear($key1, $key2 = null);
	}
	public function HasData($key1, $key2, & $out = null)
	{
		return $this->cache->HasData($key1, $key2, $out);
	}
	public function PutDataIfMissing($key1, $key2, $value)
	{
		$this->cache->PutDataIfMissing($key1, $key2, $value);
	}

	public function PutData($key1, $key2, $value)
	{
		$this->cache->PutData($key1, $key2, $value);
	}
}



