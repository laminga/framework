<?php

namespace minga\framework\locking;

use minga\framework\Context;

class QueueProcessLock extends Lock
{
	public function __construct($queueSubFolder)
	{
		$folder = Context::Paths()->GetQueuePath() . '/' . $queueSubFolder;
		parent::__construct($folder, 'process');
	}
}
