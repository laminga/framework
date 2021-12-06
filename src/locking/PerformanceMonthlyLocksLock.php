<?php

namespace minga\framework\locking;

use minga\framework\Context;

class PerformanceMonthlyLocksLock extends SingleWriterLock
{
	public function __construct()
	{
		$folder = Context::Paths()->GetPerformanceLocalPath();
		parent::__construct($folder, 'monthlyLocks');
	}
}
