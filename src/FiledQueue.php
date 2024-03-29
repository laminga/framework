<?php

namespace minga\framework;

use minga\framework\locking\Lock;

class FiledQueue
{
	private array $valuesToQueue = [];

	/** @var FiledQueue[] */
	private static array $allFiles = [];

	private string $file;
	private string $path;
	private Lock $lock;

	public function __construct(Lock $lock, string $path, string $file)
	{
		$this->lock = $lock;
		$this->path = $path;
		$this->file = $file;
	}

	public static function Create(Lock $lock, string $path, string $file) : FiledQueue
	{
		$filename = $path . "/" . $file;
		if (isset(self::$allFiles[$filename]))
			return self::$allFiles[$filename];

		$ret = new FiledQueue($lock, $path, $file);
		self::$allFiles[$filename] = $ret;
		return $ret;
	}

	public static function Clear() : void
	{
		self::$allFiles = [];
	}

	public static function Commit() : void
	{
		Profiling::BeginTimer();
		krsort(self::$allFiles);

		foreach(self::$allFiles as $value)
			self::TryFlush($value);
		self::$allFiles = [];
		Profiling::EndTimer();
	}

	private static function TryFlush(FiledQueue $value) : void
	{
		try
		{
			$value->Flush();
		}
		catch(\Exception $e)
		{
			Log::HandleSilentException($e);
		}
	}

	public function Append($value) : void
	{
		$this->valuesToQueue[] = $value;
	}

	public function Flush() : void
	{
		if (count($this->valuesToQueue) == 0)
			return;
		Profiling::BeginTimer();

		$filename = $this->path . "/" . $this->file;
		$this->lock->LockWrite();

		IO::AppendLines($filename, $this->valuesToQueue);

		$this->lock->Release();
		Profiling::EndTimer();
	}
}
