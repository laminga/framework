<?php

namespace minga\framework;

class FiledQueue
{
	private $valuesToQueue = array();

	private static $allFiles = array();

	private $file;
	private $folder;
	private $lock;

	public static function Create($lock, $folder, $file)
	{
		$filename = $folder . "/" . $file;
		if (array_key_exists($filename, self::$allFiles))
			return self::$allFiles[$filename];
		else
		{
			$ret = new FiledQueue($lock, $folder, $file);
			self::$allFiles[$filename] = $ret;
			return $ret;
		}
	}

	public static function Clear()
	{
		self::$allFiles = array();
	}
	public static function Commit()
	{
		Profiling::BeginTimer();
		krsort(self::$allFiles);

		foreach(self::$allFiles as $value)
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
		self::$allFiles = array();
		Profiling::EndTimer();
	}

	public function __construct($lock, $folder, $file)
	{
		$this->lock = $lock;
		$this->folder = $folder;
		$this->file = $file;
	}

	public function Append($value)
	{
		$this->valuesToQueue[] = $value;
	}

	public function Flush()
	{
		if (sizeof($this->valuesToQueue) == 0) return;
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
