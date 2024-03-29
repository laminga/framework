<?php

namespace minga\framework\locking;

use minga\framework\Context;

class BackupLock extends Lock
{
	public function __construct(string $set)
	{
		$folder = Context::Paths()->GetBackupLocalPath();
		parent::__construct($folder, $set);
	}
}
