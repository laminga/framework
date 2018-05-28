<?php


namespace minga\framework\locking;

class NullLock
{
	public $isLocked = false;
	public $isWriteLocked = false;

	public function __construct()
	{
	}

	public function LockRead()
	{
	}

	public function LockWrite()
	{
	}

	public function Release()
	{
	}

}
