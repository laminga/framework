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

	/** @var int */
	public $Enabled = self::Enabled;

	/** @var int */
	public $FileSystemMode = self::SQLITE3;

	public function CreateFileCache($path)
	{
		if ($this->FileSystemMode == self::SQLITE3)
			return new BaseTwoLevelStringSQLiteCache($path);

		return new BaseTwoLevelStringFileCache($path);
	}
}
