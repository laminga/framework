<?php

namespace minga\framework;

use minga\framework\settings\PathSettings;

class AppPaths
{
	private function PathName() : PathSettings
	{
		return Context::Settings()->PathNames();
	}

	public function GetRoot() : string
	{
		return Context::Settings()->rootPath;
	}

	public function GetStorageRoot() : string
	{
		return Context::Settings()->storagePath;
	}

	public function GetFrameworkPath() : string
	{
		return __DIR__;
	}

	public function GetBinPath() : string
	{
		return $this->GetRoot() . $this->PathName()->BinPath;
	}

	public function GetStorageData() : string
	{
		return $this->GetStorageRoot() . $this->PathName()->StorageData;
	}

	public function GetBackupLocalPath() : string
	{
		return Context::Paths()->GetStorageRoot() . $this->PathName()->BackupLocalPath;
	}

	public function GetQueuePath() : string
	{
		$path = $this->GetStorageRoot() . $this->PathName()->QueuePath;
		IO::EnsureExists($path);
		return $path;
	}

	public function GetCronJobsPath() : string
	{
		$path = $this->GetStorageRoot() . $this->PathName()->CronJobsPath;

		IO::EnsureExists($path);
		return $path;
	}

	public function GetHtmlPurifierCachePath() : string
	{
		$path = Context::Paths()->GetStorageCaches() . $this->PathName()->HtmlPurifierCachePath;
		IO::EnsureExists($path);
		return $path;
	}

	public function GetStorageCaches() : string
	{
		return $this->GetStorageRoot() . $this->PathName()->StorageCaches;
	}

	public function GetTokensPath() : string
	{
		return $this->GetStorageRoot() . $this->PathName()->TokensPath;
	}

	public function GetTableUpdatePath(): string
	{
		return $this->GetStorageRoot() . $this->PathName()->TableUpdatePath;
	}

	public function GetFeedbackPath() : string
	{
		$path = $this->GetStorageRoot() . $this->PathName()->FeedbackPath;
		IO::EnsureExists($path);
		return $path;
	}

	public function GetLogLocalPath() : string
	{
		return $this->GetStorageRoot() . $this->PathName()->LogLocalPath;
	}

	public function GetDumpLocalPath() : string
	{
		return $this->GetStorageRoot() . $this->PathName()->DumpLocalPath;
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

	public function GetCronJobsScriptPath() : string
	{
		return Context::Paths()->GetRoot() . $this->PathName()->CronJobsScriptPath;
	}

	public function GetTwigCache() : string
	{
		return Context::Paths()->GetRoot() . $this->PathName()->TwigCache;
	}

	public function GetTrafficLocalPath() : string
	{
		$ret = $this->GetStorageRoot() . $this->PathName()->TrafficLocalPath;
		IO::EnsureExists($ret);
		return $ret;
	}

	public function GetSearchLogLocalPath() : string
	{
		$ret = $this->GetStorageRoot() . $this->PathName()->SearchLogLocalPath;
		IO::EnsureExists($ret);
		return $ret;
	}

	public function GetPerformanceLocalPath() : string
	{
		$ret = $this->GetStorageRoot() . $this->PathName()->PerformanceLocalPath;
		IO::EnsureExists($ret);
		return $ret;
	}

	public function GetMemoryPeakPath() : string
	{
		$ret = $this->GetLogLocalPath() . $this->PathName()->MemoryPeakPath;
		IO::EnsureExists($ret);
		return $ret;
	}

	public function GetTempPath() : string
	{
		$ret = $this->GetStorageRoot() . $this->PathName()->TempPath;
		IO::EnsureExists($ret);
		return $ret;
	}

	public function GetBucketsPath() : string
	{
		return $this->GetStorageRoot() . $this->PathName()->BucketsPath;
	}

	public function GetTranslationsPath() : string
	{
		return $this->GetRoot() . $this->PathName()->TranslationsPath;
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
		return $this->GetFrameworkPath() . $this->PathName()->FrameworkTestsPath;
	}

	public function GetFrameworkTestDataPath() : string
	{
		return $this->GetFrameworkTestsPath() . $this->PathName()->FrameworkTestDataPath;
	}

	public function GetMpdfTempPath() : string
	{
		$ret = $this->GetTempPath() . $this->PathName()->MpdfTempPath;
		IO::EnsureExists($ret);
		return $ret;
	}
}
