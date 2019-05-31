<?php

namespace minga\framework\caching;

use minga\framework\Context;
use minga\framework\Profiling;
use minga\framework\Serializator;
use minga\framework\Log;

class TwoLevelObjectCache
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
		try
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
		catch(\Exception $e)
		{
			$out = null;
			Log::HandleSilentException($e);
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
		try
		{
			$this->cache->PutData($key1, $key2, Serializator::Serialize($value));
		}
		catch(\Exception $e)
		{
			Log::HandleSilentException($e);
		}
	}
}



