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

	public function GetStorageCaches()
	{
		return $this->GetStorageRoot() . "/caches";
	}

	public function GetTokensPath()
	{
		return $this->GetStorageRoot() . "/tokens";
	}

	public function GetLogLocalPath()
	{
		return $this->GetStorageRoot() . '/log';
	}

	public static function GetTwigCache()
	{
		return Context::Paths()->GetRoot() . "/compilation_cache";
	}

	public function GetMockPath()
	{
		return  $this->GetStorageRoot() . "/mock";
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
	public function GetTempPath()
	{
		return $this->GetStorageRoot() . '/temp';
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

	public function GetMpdfTempPath()
	{
		$ret = $this->GetTempPath() . '/mpdftemp';
		IO::EnsureExists($ret);
		return $ret;
	}

}
