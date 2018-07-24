<?php

namespace minga\framework\locking;

use minga\framework\Context;

class SearchLogLock extends Lock
{
	public function __construct()
	{
		$folder = Context::Paths()->GetSearchLogLocalPath();
		parent::__construct($folder);
	}
}
