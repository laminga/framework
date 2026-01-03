<?php

namespace minga\framework\caching;

use minga\framework\Context;

class TwoLevelStringCache
{
	private $cache;

	public function __construct($path, $limitMB = -1)
	{
		$this->cache = Context::Settings()->Cache()->CreateFileCache($path, $limitMB);
	}

	public function Clear($key1 = null, $key2 = null) : void
	{
		$this->cache->Clear($key1, $key2 = null);
	}

	public function HasData($key1, $key2, &$out = null) : bool
	{
		return $this->cache->HasData($key1, $key2, $out);
	}

	public function PutDataIfMissing($key1, $key2, $value) : void
	{
		$this->cache->PutDataIfMissing($key1, $key2, $value);
	}

	public function PutData($key1, $key2, $value) : void
	{
		$this->cache->PutData($key1, $key2, $value);
	}
}



