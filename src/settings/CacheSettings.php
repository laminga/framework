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

	public function CreateFileCache($path, $avoidSqLite = false)
	{
		if ($this->FileSystemMode == self::SQLITE3 && !$avoidSqLite)
			return new BaseTwoLevelStringSQLiteCache($path);

		return new BaseTwoLevelStringFileCache($path);
	}
}
