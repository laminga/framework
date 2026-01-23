<?php

namespace minga\framework;

use minga\framework\locking\ZipLock;

class Zipping
{
	/** @var \ZipArchive[] */
	private static array $allFiles = [];
	/** @var ZipLock[] */
	private static array $allLocks = [];

	public static function GetStream(string $filename) : string
	{
		$path = dirname($filename);
		$file = basename($filename);
		return 'zip://' . $path . '/content.zip#' . $file;
	}

	public static function UnStream(string $filename, ?string &$outFile = '') : string
	{
		$filename = substr($filename, 6);
		$parts = explode('#', $filename);
		$outFile = $parts[1];
		return $parts[0];
	}

	public static function GetFiles(string $filesPath, string $pattern) : array
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

	public static function FileMTime(string $filename) : int
	{
		if (Str::StartsWith($filename, "streams:"))
			return (int)SQLiteList::GetNamedStreamDateTime($filename);
		else if (self::IsZipped($filename) == false)
			return IO::FileMTime($filename);
		$stat = self::GetStat($filename);
		if ($stat == null)
			throw new \ErrorException("GetStat falló");

		return (int)$stat['mtime'];
	}

	public static function Filesize(string $filename) : int
	{
		if (self::IsZipped($filename) == false)
		{
			if(file_exists($filename))
				return filesize($filename);
			return 0;
		}

		$stat = self::GetStat($filename);
		if ($stat == null)
			return 0;

		return (int)$stat['size'];
	}

	public static function GetStat(string $filename) : ?array
	{
		Profiling::BeginTimer();
		try
		{
			$path = dirname($filename);
			$file = Str::EatUntil(basename($filename), '#');
			$container = self::GetContainer($path);

			if ($container == null)
				return null;

			$index = $container->locateName($file);
			if ($index === false)
				return null;
			$ret = $container->statIndex($index);
			if ($ret === false)
				return null;
			return $ret;
		}
		finally
		{
			Profiling::EndTimer();
		}
	}

	public static function FileExists(string $filename) : bool
	{
		if (self::IsZipped($filename) == false)
			return file_exists($filename);

		return self::CompressedFileExists($filename);
	}

	private static function ExtractToGetTempFile(string $filename) : string
	{
		$path = dirname($filename);
		$file = Str::EatUntil(basename($filename), '#');
		$container = self::GetContainer($path);

		if ($container == null)
			return '';

		$tmpfile = IO::GetTempFilename();
		$container->extractTo($tmpfile, $file);
		return $tmpfile . '/' . $file;
	}

	private static function ReleaseTempFile(string $tmpFilename) : void
	{
		$path = dirname($tmpFilename);
		IO::Delete($tmpFilename);
		IO::RmDir($path);
	}

	public static function ReadEscapedIniFileWithSections(string $filename) : array
	{
		Profiling::BeginTimer();
		$ret = '';
		$tmpFile = self::ExtractToGetTempFile($filename);
		if ($tmpFile != '')
		{
			$ret = IO::ReadEscapedIniFileWithSections($tmpFile);
			self::ReleaseTempFile($tmpFile);
		}
		Profiling::EndTimer();
		return $ret;
	}

	public static function CompressedFileExists(string $filename) : bool
	{
		try
		{
			Profiling::BeginTimer();
			$path = dirname($filename);
			$file = Str::EatUntil(basename($filename), '#');
			$container = self::GetContainer($path);

			if ($container == null)
				return false;
			$index = $container->locateName($file);
			return $index !== false;
		}
		finally
		{
			Profiling::EndTimer();
		}
	}

	public static function IsZipped(string $file) : bool
	{
		return Str::StartsWith($file, 'zip://');
	}

	private static function GetContainer(string $folder) : ?\ZipArchive
	{
		if (self::IsZipped($folder))
			$folder = substr($folder, 6);

		$file = 'content.zip';
		$filename = $folder . '/' . $file;
		if (isset(self::$allFiles[$filename]))
			return self::$allFiles[$filename];

		if (file_exists($filename) == false)
			return null;

		Profiling::BeginTimer('Zipping::GetContainer');
		$ret = new \ZipArchive();
		if ($ret->open($filename) !== true)
			throw new ErrorException(Context::Trans('No se pudo abrir el archivo'));
		$lock = new ZipLock($filename);
		$lock->LockRead();
		self::$allFiles[$filename] = $ret;
		self::$allLocks[$filename] = $lock;
		Profiling::EndTimer();
		return $ret;
	}

	/**
	 * @param array|string $filename
	 * @param array|string $filesrc
	 */
	public static function AddOrUpdate(string $zipFile, $filename, $filesrc) : void
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
			for($n = 0; $n < count($filename); $n++)
			{
				if ($zip->FileExists($filename[$n]))
					$zip->FileReplace($filename[$n], $filesrc[$n], TBSZIP_FILE);
				else
					$zip->FileAdd($filename[$n], $filesrc[$n], TBSZIP_FILE);
			}
			$time = time();
			if (file_exists($filesrc[0]))
				$time = IO::FileMTime($filesrc[0]);
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

	public static function Release() : void
	{
		foreach(self::$allFiles as $value)
			$value->close();

		foreach(self::$allLocks as $lock)
			$lock->Release();

		self::$allLocks = [];
		self::$allFiles = [];
	}
}
