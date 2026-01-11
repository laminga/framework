<?php

namespace minga\framework\caching;

use minga\framework\Context;
use minga\framework\Log;
use minga\framework\Profiling;
use minga\framework\Serializator;

class TwoLevelObjectCache
{
	private $cache;

	public function __construct($path, $avoidSqLite = false, $limitMB = -1)
	{
		$this->cache = Context::Settings()->Cache()->CreateFileCache($path, $avoidSqLite, $limitMB);
	}

	public function Clear($key1 = null, $key2 = null) : void
	{
		$this->cache->Clear($key1, $key2 = null);
	}

	public function HasRawData($key1, $key2, &$out = null) : bool
	{
		try
		{
			Profiling::BeginTimer();
			$stringValue = null;
			if ($this->cache->HasRawData($key1, $key2, $stringValue))
			{
				$out = $stringValue;
				Profiling::EndTimer();
				return true;
			}
			$out = null;
			Profiling::EndTimer();
			return false;

		}
		catch(\Exception $e)
		{
			$out = null;
			Log::HandleSilentException($e);
			return false;
		}
	}

	public function HasData($key1, $key2, &$out = null) : bool
	{
		try
		{
			Profiling::BeginTimer();
			$stringValue = null;
			if ($this->cache->HasData($key1, $key2, $stringValue))
			{
				$out = Serializator::Deserialize($stringValue);
				Profiling::EndTimer();
				return true;
			}
			$out = null;
			Profiling::EndTimer();
			return false;

		}
		catch(\Exception $e)
		{
			$out = null;
			Log::HandleSilentException($e);
			return false;
		}
	}

	public function ShowInfo($key) : void
	{
		echo "Usado: " . round($this->cache->DiskSizeMB($key), 2) . " MB\n";
		echo "Recuperable: " . round($this->cache->DiskSizeMB($key) - $this->cache->DataSizeMB($key), 2) . " MB\n";
	}

	public function PutDataIfMissing($key1, $key2, $value) : void
	{
		if ($this->HasData($key1, $key2))
			return;
		$this->PutData($key1, $key2, $value);
	}

	public function PutRawData($key1, $key2, $value) : void
	{
		try
		{
			Profiling::BeginTimer();
			$this->cache->PutRawData($key1, $key2, $value);
			Profiling::EndTimer();
		}
		catch(\Exception $e)
		{
			Log::HandleSilentException($e);
		}
	}

	public function PutData($key1, $key2, $value) : void
	{
		try
		{
			Profiling::BeginTimer();
			$this->cache->PutData($key1, $key2, Serializator::Serialize($value));
			Profiling::EndTimer();
		}
		catch(\Exception $e)
		{
			Log::HandleSilentException($e);
		}
	}
}



