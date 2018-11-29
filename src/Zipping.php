<?php

namespace minga\framework;

use minga\framework\locking\ZipLock;

class Zipping
{
	private static $allFiles = array();
	private static $allLocks = array();

	public static function GetStream($filename)
	{
		$path = dirname($filename);
		$file = basename($filename);
		return "zip://" . $path . "/content.zip#" . $file;
	}

	public static function UnStream($filename, &$outFile = "")
	{
		$filename = substr($filename, 6);
		$parts = explode("#", $filename);
		$outFile = $parts[1];
		return $parts[0];
	}

	public static function GetFiles($filesPath, $pattern)
	{
		$container = self::GetContainer($filesPath);
		if ($container == null)
			return array();
		$ret = array();
		for($i = 0; $i < $container->numFiles; $i++)
		{
			$cad = $container->getNameIndex($i);
			if (Str::EndsWith($cad, $pattern))
				$ret[] = $cad;
		}
		return $ret;
	}
	public static function filemtime($filename)
	{
		if (!self::isZipped($filename))
			return filemtime($filename);
		Profiling::BeginTimer();
		$stat = self::GetStat($filename);
		Profiling::EndTimer();
		if ($stat == null)
			return "";
		else
			return intval($stat['mtime']);
	}

	public static function filesize($filename)
	{
		if (!self::isZipped($filename))
			return filesize($filename);

		Profiling::BeginTimer();
		$stat = self::GetStat($filename);
		Profiling::EndTimer();
		if ($stat == null)
			return "";
		else
			return $stat['size'];
	}
	public static function GetStat($filename)
	{
		$path = dirname($filename);
		$file = Str::EatUntil(basename($filename), "#");
		$container = self::GetContainer($path);

		if ($container == null)
		{
			return null;
		}
		else
		{
			$index = $container->locateName($file);
			if ($index === false)
			{
				return null;
			}
			else
			{
				$stat = $container->statIndex($index);
				return $stat;
			}
		}
	}
	public static function file_exists($filename)
	{
		if (!self::isZipped($filename))
			return file_exists($filename);
		else
			return self::compressed_file_exists($filename);
	}


	private static function ExtractToGetTempFile($filename)
	{
		$path = dirname($filename);
		$file = Str::EatUntil(basename($filename), "#");
		$container = self::GetContainer($path);

		if ($container == null)
		{
			return "";
		}
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
		rmdir($path);
	}
	public static function ReadEscapedIniFileWithSections($filename)
	{
		Profiling::BeginTimer();
		$tmpFile = self::ExtractToGetTempFile($filename);
		if ($tmpFile != "")
		{
			$ret = IO::ReadEscapedIniFileWithSections($tmpFile);
			self::ReleaseTempFile($tmpFile);
		}
		else
			$ret = "";
		Profiling::EndTimer();
		return $ret;

	}

	public static function compressed_file_exists($filename)
	{
		Profiling::BeginTimer();
		$path = dirname($filename);
		$file = Str::EatUntil(basename($filename), "#");
		$container = self::GetContainer($path);

		if ($container == null)
		{
			Profiling::EndTimer();
			return false;
		}
		else
		{
			Profiling::EndTimer();
			$index = $container->locateName($file);
			if ($index === false)
				return false;
			else
				return true;
		}
	}

	public static function isZipped($file)
	{
		return Str::StartsWith($file, "zip://");
	}

	private static function GetContainer($folder)
	{
		if (self::isZipped($folder))
			$folder = substr($folder, 6);

		$file = "content.zip";
		$filename = $folder . "/" . $file;
		if (array_key_exists($filename, self::$allFiles))
			return self::$allFiles[$filename];
		else
		{
			if (!file_exists($filename))
			{
				return null;
			}
			else
			{
				Profiling::BeginTimer('Zipping::GetContainer');
				$ret = new \ZipArchive();
				if ($ret->open($filename) !== true)
					throw new \Exception("Could not open archive");
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
		$zip = new clsTbsZip();
		if (file_exists($zipFile) == false)
			$zip->CreateNew();
		else
			$zip->Open($zipFile);
		if (is_array($filename) == false)
		{
			$filename = array($filename);
			$filesrc = array($filesrc);
		}
		for($n = 0; $n < sizeof($filename); $n++)
		{
			if ($zip->FileExists($filename[$n]))
				$zip->FileReplace($filename[$n], $filesrc[$n], TBSZIP_FILE);
			else
				$zip->FileAdd($filename[$n], $filesrc[$n], TBSZIP_FILE);
		}
		$time = filemtime($filesrc[0]);
		$zip->now = $time;
		$zip->Flush(TBSZIP_FILE, $zipFile . "tmp", "");
		$zip->Close();
		IO::Move($zipFile . "tmp", $zipFile);
	}
	public static function Release()
	{
		foreach(self::$allFiles as $key => $value)
			$value->close();

		foreach(self::$allLocks as $key => $lock)
			$lock->Release();

		self::$allLocks= array();
		self::$allFiles = array();

	}
}
