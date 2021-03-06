<?php

namespace minga\framework\locking;

use minga\framework\Context;

class QueueLock extends Lock
{
	public function __construct()
	{
		$folder = Context::Paths()->GetQueuePath();
		parent::__construct($folder, 'queue');
	}
}
