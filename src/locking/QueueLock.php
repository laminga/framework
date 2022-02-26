<?php

namespace minga\framework\locking;

use minga\framework\Context;

class QueueLock extends Lock
{
	public function __construct($queueSubFolder)
	{
		$folder = Context::Paths()->GetQueuePath() . '/' . $queueSubFolder;
		parent::__construct($folder, 'items');
	}
}
