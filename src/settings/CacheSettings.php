<?php

namespace minga\framework\settings;

use minga\framework\caching\BaseTwoLevelStringFileCache;
use minga\framework\caching\BaseTwoLevelStringSQLiteCache;

class CacheSettings
{
	const Disabled = 0;
	const Enabled = 1;
	const DisabledWrite = 2;

	const FILE = 0;
	const SQLITE3 = 1;

	public $Enabled = self::Enabled;

	public $FileSystemMode = self::SQLITE3;

	public function CreateFileCache($path)
	{
		if ($this->FileSystemMode === self::SQLITE3)
			return new BaseTwoLevelStringSQLiteCache($path);
		else
			return new BaseTwoLevelStringFileCache($path);
	}
}
