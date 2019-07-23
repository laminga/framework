<?php

namespace minga\framework;

use minga\framework\locking\ZipLock;

class Zipping
{
	private static $allFiles = [];
	private static $allLocks = [];

	public static function GetStream($filename)
	{
		$path = dirname($filename);
		$file = basename($filename);
		return 'zip://' . $path . '/content.zip#' . $file;
	}

	public static function UnStream($filename, &$outFile = '')
	{
		$filename = substr($filename, 6);
		$parts = explode('#', $filename);
		$outFile = $parts[1];
		return $parts[0];
	}

	public static function GetFiles($filesPath, $pattern)
	{
		Profiling::BeginTimer();
		try
		{
			$container = self::GetContainer($filesPath);
			if ($container == null)
				return [];
			$ret = [];
			for($i = 0; $i < $container->numFiles; $i++)
			{
				$cad = $container->getNameIndex($i);
				if (Str::EndsWith($cad, $pattern))
					$ret[] = $cad;
			}
			return $ret;
		}
		finally
		{
			Profiling::EndTimer();
		}
	}

	public static function FileMTime($filename)
	{
		if (self::IsZipped($filename) == false)
			return IO::FileMTime($filename);

		$stat = self::GetStat($filename);
		if ($stat == null)
			return false;

		return (int)$stat['mtime'];
	}

	public static function Filesize($filename)
	{
		if (self::IsZipped($filename) == false)
		{
			if(file_exists($filename))
				return filesize($filename);
			return false;
		}

		$stat = self::GetStat($filename);
		if ($stat == null)
			return false;

		return $stat['size'];
	}

	public static function GetStat($filename)
	{
		Profiling::BeginTimer();
		try
		{
			$path = dirname($filename);
			$file = Str::EatUntil(basename($filename), '#');
			$container = self::GetContainer($path);

			if ($container == null)
				return null;
			else
			{
				$index = $container->locateName($file);
				if ($index === false)
					return null;
				else
					return $container->statIndex($index);
			}
		}
		finally
		{
			Profiling::EndTimer();
		}
	}

	public static function FileExists($filename)
	{
		if (self::IsZipped($filename) == false)
			return file_exists($filename);
		else
			return self::CompressedFileExists($filename);
	}

	private static function ExtractToGetTempFile($filename)
	{
		$path = dirname($filename);
		$file = Str::EatUntil(basename($filename), '#');
		$container = self::GetContainer($path);

		if ($container == null)
			return '';
		else
		{
			$tmpfile = IO::GetTempFilename();
			$container->extractTo($tmpfile, $file);
			return $tmpfile . '/' . $file;
		}
	}

	private static function ReleaseTempFile($tmpFilename)
	{
		$path = dirname($tmpFilename);
		IO::Delete($tmpFilename);
		IO::RmDir($path);
	}

	public static function ReadEscapedIniFileWithSections($filename)
	{
		Profiling::BeginTimer();
		$tmpFile = self::ExtractToGetTempFile($filename);
		if ($tmpFile != '')
		{
			$ret = IO::ReadEscapedIniFileWithSections($tmpFile);
			self::ReleaseTempFile($tmpFile);
		}
		else
			$ret = '';
		Profiling::EndTimer();
		return $ret;

	}

	public static function CompressedFileExists($filename)
	{
		try
		{
			Profiling::BeginTimer();
			$path = dirname($filename);
			$file = Str::EatUntil(basename($filename), '#');
			$container = self::GetContainer($path);

			if ($container == null)
				return false;
			else
			{
				$index = $container->locateName($file);
				return ($index !== false);
			}
		}
		finally
		{
			Profiling::EndTimer();
		}
	}

	public static function IsZipped($file)
	{
		return Str::StartsWith($file, 'zip://');
	}

	private static function GetContainer($folder)
	{
		if (self::IsZipped($folder))
			$folder = substr($folder, 6);

		$file = 'content.zip';
		$filename = $folder . '/' . $file;
		if (array_key_exists($filename, self::$allFiles))
			return self::$allFiles[$filename];
		else
		{
			if (file_exists($filename) == false)
				return null;
			else
			{
				Profiling::BeginTimer('Zipping::GetContainer');
				$ret = new \ZipArchive();
				if ($ret->open($filename) !== true)
					throw new ErrorException('Could not open archive');
				$lock = new ZipLock($filename);
				$lock->LockRead();
				self::$allFiles[$filename] = $ret;
				self::$allLocks[$filename] = $lock;
				Profiling::EndTimer();
				return $ret;
			}
		}
	}

	public static function AddOrUpdate($zipFile, $filename, $filesrc)
	{
		try
		{
			$zip = new clsTbsZip();
			if (file_exists($zipFile) == false)
				$zip->CreateNew();
			else
				$zip->Open($zipFile);
			if (is_array($filename) == false)
			{
				$filename = [$filename];
				$filesrc = [$filesrc];
			}
			for($n = 0; $n < sizeof($filename); $n++)
			{
				if ($zip->FileExists($filename[$n]))
					$zip->FileReplace($filename[$n], $filesrc[$n], TBSZIP_FILE);
				else
					$zip->FileAdd($filename[$n], $filesrc[$n], TBSZIP_FILE);
			}
			$time = IO::FileMTime($filesrc[0]);
			if($time === false)
				$time = time();
			$zip->now = $time;
			$zip->Flush(TBSZIP_FILE, $zipFile . 'tmp', '');
			$zip->Close();
			IO::Move($zipFile . 'tmp', $zipFile);
		}
		catch(\Exception $e)
		{
			Log::HandleSilentException($e);
		}
	}

	public static function Release()
	{
		foreach(self::$allFiles as $key => $value)
			$value->close();

		foreach(self::$allLocks as $key => $lock)
			$lock->Release();

		self::$allLocks= [];
		self::$allFiles = [];
	}
}
