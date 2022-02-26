<?php

namespace minga\framework;

class FiledQueue
{
	/** @var array */
	private $valuesToQueue = [];

	/** @var FiledQueue[] */
	private static $allFiles = [];

	private $file;
	private $path;
	private $lock;

	public function __construct($lock, $path, $file)
	{
		$this->lock = $lock;
		$this->path = $path;
		$this->file = $file;
	}

	public static function Create($lock, string $path, string $file) : FiledQueue
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

	private static function TryFlush($value) : void
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

		$this->AppendLines($filename, $this->valuesToQueue);

		$this->lock->Release();
		Profiling::EndTimer();
	}

	//TODO: esta funcion está en IO, confirmar
	private function AppendLines(string $file, $lines) : bool
	{
		$handle = fopen($file, 'a');
		if ($handle === false)
			return false;

		foreach($this->valuesToQueue as $value)
		{
			if (fwrite($handle, $value . "\r\n") === false)
			{
				fclose($handle);
				return false;
			}
		}
		fclose($handle);
		return true;
	}
}
