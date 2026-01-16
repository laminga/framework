<?php

namespace minga\framework\settings;

use minga\framework\caching\BaseTwoLevelStringFileCache;
use minga\framework\caching\BaseTwoLevelStringSQLiteCache;

class CacheSettings
{
	public const Disabled = 0;
	public const Enabled = 1;
	public const DisabledWrite = 2;

	public const FILE = 0;
	public const SQLITE3 = 1;

	public int $Enabled = self::Enabled;

	public int $FileSystemMode = self::SQLITE3;

	public function CreateFileCache(string $path, bool $avoidSqLite = false, int $limitMB = -1)
	{
		if ($this->FileSystemMode == self::SQLITE3 && $avoidSqLite == false)
			return new BaseTwoLevelStringSQLiteCache($path, false, $limitMB);
		return new BaseTwoLevelStringFileCache($path, false, $limitMB);
	}
}
