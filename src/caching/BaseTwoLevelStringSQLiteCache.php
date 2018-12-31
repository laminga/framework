<?php

namespace minga\framework\caching;

use minga\framework\Context;
use minga\framework\IO;
use minga\framework\Profiling;
use minga\framework\settings\CacheSettings;
use minga\framework\SQLiteList;

class BaseTwoLevelStringSQLiteCache
{
	private $path;
	private $db;

	public function __construct($path)
	{
		$this->path = Context::Paths()->GetStorageCaches() . '/services/' . $path;
		IO::EnsureExists($this->path);
		$this->db = new SQLiteList('k', array('v'));
	}

	private function OpenRead($key = null)
	{
		$this->db->Open($this->ResolveFilename($key), true);
	}

	private function OpenWrite($key = null)
	{
		$this->db->Open($this->ResolveFilename($key));
	}

	private function Close()
	{
		$this->db->Close();
	}

	public function Clear($key1 = null, $key2 = null)
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
		}
		else
		{
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
	}

	public function HasData($key1, $key2, &$value = null)
	{
		if (Context::Settings()->Cache()->Enabled !== CacheSettings::Enabled)
		{
			return false;
		}
		$levelKey = ($key2 === null ? null : $key1);
		$valueKey = ($key2 === null ? $key1 : $key2);
		$this->OpenRead($levelKey);	
		$value = $this->db->ReadValue($valueKey, 'v');
		$this->Close();
		if ($value !== null)
		{
			$value = $value[1];
			return true;
		}
		else
			return false;
	}

	private function ResolveFilename($key1)
	{
		if ($key1 === null)
			$key1 = 'cache';
	
		$file = $this->path . "/" . $key1 . ".db";
		return $file;
	}

	public function PutDataIfMissing($key1, $key2, $value)
	{
		if (Context::Settings()->Cache()->Enabled === CacheSettings::Disabled || $this->HasData($key1, $key2))
			return;
		$this->PutData($key1, $key2, $value);
	}

	public function PutData($key1, $key2, $value)
	{
		if (Context::Settings()->Cache()->Enabled === CacheSettings::Disabled)
			return;
		$levelKey = ($key2 === null ? null : $key1);
		$valueKey = ($key2 === null ? $key1 : $key2);
		$this->OpenWrite($levelKey);
		$this->db->InsertOrUpdate($valueKey, $value);
		$this->Close();
	}

}

