<?php

namespace minga\framework\caching;

use minga\framework\Context;
use minga\framework\IO;
use minga\framework\settings\CacheSettings;
use minga\framework\SQLiteList;
use minga\framework\Str;

class BaseTwoLevelBlobSQLiteCache
{
	private $path;
	private SQLiteList $db;

	public function __construct($path, bool $isAbsolutePath = false)
	{
		if ($isAbsolutePath)
			$this->path = $path;
		else
			$this->path = Context::Paths()->GetStorageCaches() . '/services/' . $path;
		IO::EnsureExists($this->path);
		$this->db = new SQLiteList('k', ['v', 'time'], ['length'], null, ['v']);
	}

	/**
	 * @phpstan-impure
	 */
	private function OpenRead($key = null, bool $throwLockErrors = true) : bool
	{
		try
		{
			$this->db->Open($this->ResolveFilename($key), true);
			return true;
		}
		catch(\Exception $e)
		{
			if (Str::Contains($e->getMessage(), "Unable to execute statement: attempt to write a readonly database"))
				unlink($this->ResolveFilename($key));

			if (Str::Contains($e->getMessage(), "database is locked") == false || $throwLockErrors)
				throw $e;

			return false;
		}
	}

	/**
	 * @phpstan-impure
	 */
	private function OpenWrite($key = null, bool $throwLockErrors = true) : bool
	{
		try
		{
			$this->db->Open($this->ResolveFilename($key));
			return true;
		}
		catch(\Exception $e)
		{
			if (Str::Contains($e->getMessage(), "database is locked") == false || $throwLockErrors)
				throw $e;
			return false;
		}
	}

	private function Close() : void
	{
		$this->db->Close();
	}

	public function Clear($key1 = null, $key2 = null) : void
	{
		if ($key1 == null)
		{
			IO::ClearDirectory($this->path);
			return;
		}
		$levelKey = ($key2 === null ? null : $key1);
		$valueKey = ($key2 === null ? $key1 : $key2);
		if ($levelKey !== null)
		{
			// Es de 2 niveles
			$this->OpenWrite($levelKey);
			$this->db->Delete($valueKey);
			$this->db->Close();
			return;
		}

		$file = $this->ResolveFilename(null);
		if (file_exists($file))
		{
			// Es de 1 nivel y pide borrar el key
			$this->OpenWrite(null);
			$this->db->Delete($valueKey);
			$this->db->Close();
		}
		else
		{
			// Es de 2 niveles y pide borrar todo
			$this->OpenWrite($key1);
			$this->db->DeleteAll();
			$this->db->Close();
			$file = $this->ResolveFilename($key1);
			IO::Delete($file);
		}
	}

	public function HasData($key1, $key2, &$value = null) : bool
	{
		if (Context::Settings()->Cache()->Enabled !== CacheSettings::Enabled)
			return false;

		$levelKey = ($key2 === null ? null : $key1);
		$valueKey = ($key2 === null ? $key1 : $key2);

		if ($this->OpenRead($levelKey, false) == false)
		{
			sleep(1);
			if ($this->OpenRead($levelKey, false) == false)
				return false;
		}
		$value = $this->db->ReadBlobValue($valueKey, 'v');
		if ($value !== null)
		{
			$value = $value;
			return true;
		}
		return false;
	}

	private function ResolveFilename($key1) : string
	{
		if ($key1 === null)
			$key1 = 'cache';

		return $this->path . "/" . $key1 . ".db";
	}

	public function PutDataIfMissing($key1, $key2, $value) : void
	{
		if (Context::Settings()->Cache()->Enabled === CacheSettings::Disabled || $this->HasData($key1, $key2))
			return;
		$this->PutData($key1, $key2, $value);
	}

	public function PutData($key1, $key2, $valueFilename, $timeStamp = null) : void
	{
		if (Context::Settings()->Cache()->Enabled === CacheSettings::Disabled)
			return;

		$levelKey = ($key2 === null ? null : $key1);
		$valueKey = ($key2 === null ? $key1 : $key2);
		if ($this->OpenWrite($levelKey, false) == false)
		{
			sleep(1);
			if ($this->OpenWrite($levelKey, false) == false)
				return;
		}
		try
		{
			$namedStream = SQLiteList::CreateNamedStreamFromFile($valueFilename);
			$length = filesize($valueFilename);
			$time = ($timeStamp ? $timeStamp : filemtime($valueFilename));
			$this->db->InsertOrUpdateBlob($valueKey, $namedStream, $time, $length);
		}
		catch(\Exception $e)
		{
			$err = $e->getMessage();
			if (Str::Contains($err, "Unable to prepare statement: 1, no such table: data"))
				$this->db->Truncate();
			throw $e;
		}
		$this->Close();
	}
}

