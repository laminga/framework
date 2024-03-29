<?php

namespace minga\framework\locking;

use minga\framework\Context;
use minga\framework\ErrorException;
use minga\framework\IO;
use minga\framework\Performance;
use minga\framework\Profiling;

class Lock
{
	protected $handle;
	protected string $folder;
	private string $file;
	private bool $readWrite;
	public bool $isLocked = false;
	public bool $isWriteLocked = false;

	protected string $statsKey = 'default';

	private static array $locks = [];

	public function __construct(string $folder, string $file = 'lock', bool $readWrite = false)
	{
		$this->file = $file;
		$this->folder = $folder;
		$this->statsKey = static::class;
		$this->readWrite = $readWrite;
	}

	public function LockRead() : void
	{
		if ($this->LockUsed(false))
			return;
		$this->doLock(LOCK_SH);
	}

	public function LockWrite() : void
	{
		if ($this->LockUsed(true))
			return;
		$this->doLock(LOCK_EX);
		$this->isWriteLocked = true;
	}

	private function LockUsed(bool $write) : bool
	{
		$file = $this->ResolveFilename();

		if (isset(self::$locks[$file]))
		{
			// ya está lockeado
			$values = self::$locks[$file];
			if ($write && $values[1] == false)
				throw new ErrorException('No se puede obtener el lock de escritura mientras el lock de lectura está en uso.');
			self::$locks[$file] = [++$values[0], $values[1]];
			return true;
		}


		// empieza él
		self::$locks[$file] = [1, $write];
		return false;

	}

	private function ReleaseUsed() : bool
	{
		$file = $this->ResolveFilename();

		if (isset(self::$locks[$file]))
		{
			// ya está lockeado
			$values = self::$locks[$file];
			if ($values[0] > 1)
			{
				self::$locks[$file] = [--$values[0], $values[1]];
				return true;
			}
			unset(self::$locks[$file]);
			return false;
		}
		throw new ErrorException('No se pudo liberar el lock.');
	}

	public function ResolveFilename() : string
	{
		return $this->folder . '/' . $this->file . '.lock';
	}

	private function doLock($type) : void
	{
		$mode = ($this->readWrite ? 'c+' : 'w+');
		$this->handle = fopen($this->ResolveFilename(), $mode);

		Performance::BeginLockedWait($this->statsKey);

		$sleepTime = 100; // milliseconds
		$wait = 10; // seconds

		$maxCycles = $wait * 1000 / $sleepTime;
		$hadToWait = false;
		$loops = 0;
		while (flock($this->handle, $type | LOCK_NB) === false)
		{
			$hadToWait = true;
			// pausa por 100 ms
			usleep($sleepTime * 1000);
			$loops++;
			if ($loops == $maxCycles)
			{
				fclose($this->handle);
				$this->handle = null;
				$this->isLocked = false;
				Performance::EndLockedWait($hadToWait);
				throw new ErrorException(Context::Trans('No fue posible obtener acceso al elemento solicitado. Intente nuevamente en unos instantes.'));
			}
		}
		$this->isLocked = true;
		$this->AppendLockInfo('Locked ', $type);
		Performance::EndLockedWait($hadToWait);
	}

	public function Release(bool $deleteLockFile = false) : void
	{
		if ($this->ReleaseUsed())
		{
			$this->AppendLockInfo('Se intentó liberar un lock en uso: ');
			return;
		}
		if ($this->handle != null)
		{
			$this->AppendLockInfo('Liberar: ');

			flock($this->handle, LOCK_UN);
			fclose($this->handle);
			if ($deleteLockFile)
			{
				$file = $this->ResolveFilename();
				IO::Delete($file);
			}
			$this->handle = null;
		}
		else
			$this->AppendLockInfo('Se intentó liberar un lock nulo: ');

		$this->isLocked = false;
		$this->isWriteLocked = false;
	}

	private function AppendLockInfo(string $info, $type = -1) : void
	{
		if (Profiling::IsProfiling() == false)
			return;
		if ($type != -1)
		{
			if ($type == LOCK_EX)
				$info .= ' write: ';
			else
				$info .= ' read: ';
		}
		$folder = IO::GetRelativePath($this->folder);

		Profiling::AppendLockInfo($this->statsKey . ': ' . $info . $folder . '/' . $this->file);
	}

	public static function ReleaseAllStaticLocks() : void
	{
		while (PerformanceDaylyLocksLock::IsWriting())
			PerformanceDaylyLocksLock::EndWrite();
		while (PerformanceDaylyUsageLock::IsWriting())
			PerformanceDaylyUsageLock::EndWrite();
		while (PerformanceDaylyUserLock::IsWriting())
			PerformanceDaylyUserLock::EndWrite();

		while (PerformanceMonthlyLocksLock::IsWriting())
			PerformanceMonthlyLocksLock::EndWrite();
		while (PerformanceMonthlyUsageLock::IsWriting())
			PerformanceMonthlyUsageLock::EndWrite();
		while (PerformanceMonthlyUserLock::IsWriting())
			PerformanceMonthlyUserLock::EndWrite();
		while (PerformanceMonthlyDayLock::IsWriting())
			PerformanceMonthlyDayLock::EndWrite();
	}
}
