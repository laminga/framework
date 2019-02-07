<?php

namespace minga\framework\locking;

use minga\framework\IO;
use minga\framework\Performance;
use minga\framework\Profiling;

class Lock
{
	private $handle;
	protected $folder;
	private $file;
	public $isLocked = false;
	public $isWriteLocked = false;

	protected $statsKey = "default";

	private static $locks = array();

	public function __construct($folder, $file="lock")
	{
		$this->file = $file;
		$this->folder = $folder;
		$this->statsKey = get_class($this);
	}

	public function LockRead()
	{
		if ($this->LockUsed(false))
			return;
		$this->doLock(LOCK_SH);
	}

	public function LockWrite()
	{
		if ($this->LockUsed(true))
			return;
		$this->doLock(LOCK_EX);
		$this->isWriteLocked = true;
	}

	private function LockUsed($write)
	{
		$file = $this->ResolveFilename();

		if (array_key_exists($file, self::$locks))
		{
			// ya está lockeado
			$values = self::$locks[$file];
			if ($write && $values[1] == false)
				throw new \Exception("WriteLock could not be taken while ReadLock is used.");
			self::$locks[$file] = array(++$values[0], $values[1]);
			return true;
		}
		else
		{
			// empieza él
			self::$locks[$file] = array(1, $write);
			return false;
		}
	}
	private function ReleaseUsed()
	{
		$file = $this->ResolveFilename();

		if (array_key_exists($file, self::$locks))
		{
			// ya está lockeado
			$values = self::$locks[$file];
			if ($values[0] > 1)
			{
				self::$locks[$file] = array(--$values[0], $values[1]);
				return true;
			}
			else
			{
				unset(self::$locks[$file]);
				return false;
			}
		}
		else
		{
			throw new \Exception("The lock could not be released.");
		}
	}

	public function ResolveFilename()
	{
		return $this->folder . "/" . $this->file . ".lock";
	}

	private function doLock($type)
	{
		$this->handle = fopen($this->ResolveFilename(), "w+");

		Performance::BeginLockedWait($this->statsKey);

		$SLEEP_TIME = 100; // milliseconds
		$WAIT = 10; // seconds

		$max_cycles = $WAIT * 1000 / $SLEEP_TIME;
		$hadToWait = false;
		$loops = 0;
		while (flock($this->handle, $type | LOCK_NB) === false)
		{
			$hadToWait = true;
			// pausa por 100 ms
			usleep($SLEEP_TIME * 1000);
			$loops++;
			if ($loops == $max_cycles)
			{
				fclose($this->handle);
				$this->handle = null;
				$this->isLocked = false;
				Performance::EndLockedWait($hadToWait);
				throw new \Exception("No fue posible obtener acceso al elemento solicitado. Intente nuevamente en unos instantes.");
			}
		}
		$this->isLocked = true;
		$this->AppendLockInfo("Locked ", $type);
		Performance::EndLockedWait($hadToWait);
	}

	public function Release()
	{
		if ($this->ReleaseUsed())
		{
			$this->AppendLockInfo("Tried release on used lock: ");
			return;
		}
		if ($this->handle != null)
		{
			$this->AppendLockInfo("Release: ");

			flock($this->handle, LOCK_UN);
			fclose($this->handle);
			$this->handle = null;
		}
		else
			$this->AppendLockInfo("Tried release on null lock: ");

		$this->isLocked = false;
		$this->isWriteLocked = false;
	}
	private function AppendLockInfo($info, $type = -1)
	{
		if (Profiling::IsProfiling() == false)
			return;
		if ($type != -1)
		{
			if ($type == LOCK_EX)
				$info .= " write: ";
			else
				$info .= " read: ";
		}
		$folder = IO::GetRelativePath($this->folder);

		Profiling::AppendLockInfo($this->statsKey  . ": " . $info . $folder . "/" . $this->file);
	}

	public static function ReleaseAllStaticLocks()
	{
		while (PerformanceLock::IsWriting())
			PerformanceLock::EndWrite();
	}

}
