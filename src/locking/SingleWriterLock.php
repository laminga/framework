<?php

namespace minga\framework\locking;

use minga\framework\ErrorException;
use minga\framework\Log;

abstract class SingleWriterLock extends Lock
{
	private static $writeLock = null;
	private static $readLock = null;

	private static int $refCount = 0;

	public static function IsWriting() : bool
	{
		return self::$writeLock != null;
	}

	public static function BeginRead() : void
	{
		//TODO: esto no debería funcionar necesita un parámetro...
		self::$readLock = new static();
		self::$readLock->LockRead();
	}

	public static function EndRead() : void
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

	public static function BeginWrite() : void
	{
		if (self::$writeLock != null)
			self::$refCount++;
		else
		{
			self::$refCount = 1;
			self::$writeLock = new static();
			self::$writeLock->LockWrite();
		}
	}

	public static function EndWrite() : void
	{
		if (self::$writeLock == null)
		{
			$e = new ErrorException('Se ha intentado finalizar un ' . static::class . ' sin una inicialización asociada.');
			Log::HandleSilentException($e);
			return;
		}

		self::$refCount--;
		if (self::$refCount <= 0)
		{
			$lock = self::$writeLock;
			self::$writeLock = null;
			try
			{
				$lock->Release();
			}
			catch (\Exception $e)
			{
			}
		}
	}
}
