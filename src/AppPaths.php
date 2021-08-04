<?php

namespace minga\framework;

class AppPaths
{
	public function GetRoot()
	{
		return Context::Settings()->rootPath;
	}

	public function GetBinPath()
	{
		return $this->GetRoot() . "/cgi-bin";
	}

	public function GetStorageRoot()
	{
		return Context::Settings()->storagePath;
	}

	public function GetStorageData()
	{
		return $this->GetStorageRoot() . "/data";
	}

	public function GetQueuePath()
	{
		$path = $this->GetStorageRoot() . "/queue";
		IO::EnsureExists($path . '/ran');
		IO::EnsureExists($path . '/failed');
		return $path;
	}

	public static function GetHtmlPurifierCachePath()
	{
		$path = Context::Paths()->GetStorageCaches() . '/htmlpurifier';
		IO::EnsureExists($path);
		return $path;
	}

	public function GetStorageCaches()
	{
		return $this->GetStorageRoot() . "/caches";
	}

	public function GetTokensPath()
	{
		return $this->GetStorageRoot() . "/tokens";
	}

	public function GetFeedbackPath()
	{
		$path = $this->GetStorageRoot() . "/feedback";
		IO::EnsureExists($path);
		return $path;
	}

	public function GetLogLocalPath()
	{
		return $this->GetStorageRoot() . '/log';
	}

	public function GetDumpLocalPath()
	{
		return $this->GetStorageRoot() . '/dump';
	}

	public function GetDumpMonthlyLocalPath()
	{
		$ret = $this->GetDumpLocalPath() . '/' . Date::GetLogMonthFolder();
		IO::EnsureExists($ret);
		return $ret;
	}

	public function GetDumpMonthlyCurrentUserLocalPath()
	{
		$ret = $this->GetDumpMonthlyLocalPath() . '/' . Str::UrlencodeFriendly(Context::LoggedUser());
		IO::EnsureExists($ret);
		return $ret;
	}

	public static function GetTwigCache()
	{
		return Context::Paths()->GetRoot() . "/compilation_cache";
	}


	public function GetTrafficLocalPath()
	{
		$ret = $this->GetStorageRoot() . '/traffic';
		IO::EnsureExists($ret);
		return $ret;
	}

	public function GetSearchLogLocalPath()
	{
		$ret = $this->GetStorageRoot() . '/search';
		IO::EnsureExists($ret);
		return $ret;
	}

	public function GetPerformanceLocalPath()
	{
		$ret = $this->GetStorageRoot() . '/performance';
		IO::EnsureExists($ret);
		return $ret;
	}

	public function GetMemoryPeakPath()
	{
		$ret = $this->GetLogLocalPath() . '/memory';
		IO::EnsureExists($ret);
		return $ret;
	}

	public function GetTempPath()
	{
		$ret = $this->GetStorageRoot() . '/temp';
		IO::EnsureExists($ret);
		return $ret;
	}

	public function GetBucketsPath()
	{
		return $this->GetStorageRoot() . '/buckets';
	}

	public function GetFrameworkPath()
	{
		return __DIR__;
	}

	public function GetFrameworkDataPath()
	{
		return realpath($this->GetFrameworkPath() . '/../data');
	}

	public function GetFrameworkTestsPath()
	{
		return $this->GetFrameworkPath() . '/tests';
	}

	public function GetFrameworkTestDataPath()
	{
		return $this->GetFrameworkTestsPath() . '/data';
	}

	public function GetMpdfTempPath()
	{
		$ret = $this->GetTempPath() . '/mpdftemp';
		IO::EnsureExists($ret);
		return $ret;
	}

}
