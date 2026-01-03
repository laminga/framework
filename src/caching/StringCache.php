<?php

namespace minga\framework\caching;

use minga\framework\Context;

class StringCache
{
	private $cache;

	public function __construct($path, bool $forceFileSystem = false, int $limitMB = -1)
	{
		if ($forceFileSystem)
			$this->cache = new BaseTwoLevelStringFileCache($path);
		else
			$this->cache = Context::Settings()->Cache()->CreateFileCache($path, false, $limitMB);
	}

	public function Clear($key = null) : void
	{
		$this->cache->Clear($key);
	}

	public function HasData($key, &$out = null) : bool
	{
		return $this->cache->HasData($key, null, $out);
	}

	public function PutDataIfMissing($key, $value) : void
	{
		if ($this->HasData($key))
			return;
		$this->PutData($key, $value);
	}

	public function PutData($key, $value) : void
	{
		$this->cache->PutData($key, null, $value);
	}
}



