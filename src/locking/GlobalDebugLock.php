<?php

namespace minga\framework\locking;

use minga\framework\Context;
use minga\framework\GlobalizeDebugSession;

class GlobalDebugLock extends SingleWriterLock
{
	public function __construct()
	{
		$folder = Context::Paths()->GetTempPath();
		parent::__construct($folder, GlobalizeDebugSession::FILE);
	}
}
