<?php

namespace minga\framework;

class AppPaths
{
	public function GetRoot() : string
	{
		return Context::Settings()->rootPath;
	}

	public function GetBinPath() : string
	{
		return $this->GetRoot() . "/cgi-bin";
	}

	public function GetStorageRoot() : string
	{
		return Context::Settings()->storagePath;
	}

	public function GetStorageData() : string
	{
		return $this->GetStorageRoot() . "/data";
	}

	public static function GetBackupLocalPath() : string
	{
		return Context::Paths()->GetStorageRoot() . '/backup';
	}

	public function GetQueuePath() : string
	{
		$path = $this->GetStorageRoot() . "/queue";
		IO::EnsureExists($path);
		return $path;
	}

	public function GetCronJobsPath() : string
	{
		$path = $this->GetStorageRoot() . "/cron";
		IO::EnsureExists($path);
		return $path;
	}

	public static function GetHtmlPurifierCachePath() : string
	{
		$path = Context::Paths()->GetStorageCaches() . '/htmlpurifier';
		IO::EnsureExists($path);
		return $path;
	}

	public function GetStorageCaches() : string
	{
		return $this->GetStorageRoot() . "/caches";
	}

	public function GetTokensPath() : string
	{
		return $this->GetStorageRoot() . "/tokens";
	}

	public function GetFeedbackPath() : string
	{
		$path = $this->GetStorageRoot() . "/feedback";
		IO::EnsureExists($path);
		return $path;
	}

	public function GetLogLocalPath() : string
	{
		return $this->GetStorageRoot() . '/log';
	}

	public function GetDumpLocalPath() : string
	{
		return $this->GetStorageRoot() . '/dump';
	}

	public function GetDumpMonthlyLocalPath() : string
	{
		$ret = $this->GetDumpLocalPath() . '/' . Date::GetLogMonthFolder();
		IO::EnsureExists($ret);
		return $ret;
	}

	public function GetDumpMonthlyCurrentUserLocalPath() : string
	{
		$ret = $this->GetDumpMonthlyLocalPath() . '/' . Str::UrlencodeFriendly(Context::LoggedUser());
		IO::EnsureExists($ret);
		return $ret;
	}

	public static function GetCronJobsScriptPath() : string
	{
		return Context::Paths()->GetRoot() . "/services/cron";
	}

	public static function GetTwigCache() : string
	{
		return Context::Paths()->GetRoot() . "/compilation_cache";
	}

	public function GetTrafficLocalPath() : string
	{
		$ret = $this->GetStorageRoot() . '/traffic';
		IO::EnsureExists($ret);
		return $ret;
	}

	public function GetSearchLogLocalPath() : string
	{
		$ret = $this->GetStorageRoot() . '/search';
		IO::EnsureExists($ret);
		return $ret;
	}

	public function GetPerformanceLocalPath() : string
	{
		$ret = $this->GetStorageRoot() . '/performance';
		IO::EnsureExists($ret);
		return $ret;
	}

	public function GetMemoryPeakPath() : string
	{
		$ret = $this->GetLogLocalPath() . '/memory';
		IO::EnsureExists($ret);
		return $ret;
	}

	public function GetTempPath() : string
	{
		$ret = $this->GetStorageRoot() . '/temp';
		IO::EnsureExists($ret);
		return $ret;
	}

	public function GetBucketsPath() : string
	{
		return $this->GetStorageRoot() . '/buckets';
	}

	public function GetFrameworkPath() : string
	{
		return __DIR__;
	}

	public function GetFrameworkTranslationsPath() : string
	{
		return realpath($this->GetFrameworkPath() . '/../translations');
	}

	public function GetFrameworkDataPath() : string
	{
		return realpath($this->GetFrameworkPath() . '/../data');
	}

	public function GetFrameworkTestsPath() : string
	{
		return $this->GetFrameworkPath() . '/tests';
	}

	public function GetFrameworkTestDataPath() : string
	{
		return $this->GetFrameworkTestsPath() . '/data';
	}

	public function GetMpdfTempPath() : string
	{
		$ret = $this->GetTempPath() . '/mpdftemp';
		IO::EnsureExists($ret);
		return $ret;
	}
}
