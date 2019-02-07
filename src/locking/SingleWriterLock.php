<?php

namespace minga\framework\locking;

use minga\framework\Log;

abstract class SingleWriterLock extends Lock
{
	private static $writeLock = null;
	private static $readLock = null;

	private static $refCount = 0;

	public static function IsWriting()
	{
		return (self::$writeLock != null);
	}

	public static function BeginRead()
	{
		self::$readLock = new static();
		self::$readLock->LockRead();
	}
	public static function EndRead()
	{
		$lock = self::$readLock;
		self::$readLock = null;
		if ($lock == null)
		{
			print_r(debug_backtrace());
			exit();
		}
		$lock->Release();
	}
	public static function BeginWrite()
	{
		if (self::$writeLock != null)
		{
			self::$refCount++;
		}
		else
		{
			self::$refCount = 1;
			self::$writeLock = new static();
			self::$writeLock->LockWrite();
		}
	}
	public static function EndWrite()
	{
		if (self::$writeLock == null)
		{
			$e = new \Exception("Se ha intentado finalizar un " . get_called_class() . " sin una inicialización asociada.");
			Log::HandleSilentException($e);
			return;
		}

		self::$refCount--;
		if (self::$refCount <= 0)
		{
			$lock = self::$writeLock;
			self::$writeLock = null;
			try {
				$lock->Release();
			}
			catch (\Exception $e) {
				;
			}
		}
	}
}
