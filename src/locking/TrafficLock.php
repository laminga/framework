<?php

namespace minga\framework\locking;

use minga\framework\Context;

class TrafficLock extends Lock
{
	public function __construct($set)
	{
		$folder = Context::Paths()->GetTrafficLocalPath();
		parent::__construct($folder, $set);
	}
}
