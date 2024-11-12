<?php

namespace minga\framework\locking;

use minga\framework\Context;

class CreateMergeLock extends Lock
{
	public function __construct($tableName)
	{
		$folder = Context::Paths()->GetTempPath();
		parent::__construct($folder, 'merge_' . $tableName . ".lock");
	}
}
