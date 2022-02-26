<?php

namespace minga\framework\locking;

use minga\framework\Str;

class ZipLock extends Lock
{
	public function __construct(string $filename)
	{
		$path = dirname($filename);
		$file = Str::EatFrom(basename($filename), ".");

		parent::__construct($path, $file);
	}
}
