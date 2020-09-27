<?php

namespace minga\framework;

class FiledQueue
{
	private $valuesToQueue = [];

	private static $allFiles = [];

	private $file;
	private $folder;
	private $lock;

	public function __construct($lock, $folder, $file)
	{
		$this->lock = $lock;
		$this->folder = $folder;
		$this->file = $file;
	}

	public static function Create($lock, $folder, $file)
	{
		$filename = $folder . "/" . $file;
		if (array_key_exists($filename, self::$allFiles))
			return self::$allFiles[$filename];

		$ret = new FiledQueue($lock, $folder, $file);
		self::$allFiles[$filename] = $ret;
		return $ret;
	}

	public static function Clear()
	{
		self::$allFiles = [];
	}

	public static function Commit()
	{
		Profiling::BeginTimer();
		krsort(self::$allFiles);

		foreach(self::$allFiles as $value)
			self::TryFlush($value);
		self::$allFiles = [];
		Profiling::EndTimer();
	}

	private static function TryFlush($value)
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

	public function Append($value)
	{
		$this->valuesToQueue[] = $value;
	}

	public function Flush()
	{
		if (count($this->valuesToQueue) == 0)
			return;
		Profiling::BeginTimer();

		$filename = $this->folder . "/" . $this->file;
		$this->lock->LockWrite();

		$this->AppendLines($filename, $this->valuesToQueue);

		$this->lock->Release();
		Profiling::EndTimer();
	}

	private function AppendLines($file, $lines)
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
