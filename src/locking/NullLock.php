<?php

namespace minga\framework\locking;

class NullLock
{
	public $isLocked = false;
	public $isWriteLocked = false;

	public function __construct()
	{
	}

	public function LockRead() : void
	{
	}

	public function LockWrite() : void
	{
	}

	public function Release() : void
	{
	}

}
