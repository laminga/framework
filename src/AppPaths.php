<?php

namespace minga\framework;

class AppPaths
{
	private static $useBinFromRoot = false;

	public function GetRoot()
	{
		return Context::Settings()->rootPath;
	}

	public function GetSourcePath()
	{
		return realpath($this->GetRoot() . "/src");
	}
	public function UseBinFromRoot()
	{
		self::$useBinFromRoot = true;
	}
	public function GetBinPath()
	{
		if (self::$useBinFromRoot)
			return realpath($this->GetRoot() . "/cgi-bin");
		else
			return realpath($this->GetSourcePath() . "/cgi-bin");
	}

	public function GetStorageRoot()
	{
		return realpath($this->GetRoot() . "/storage");
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
		return Context::Paths()->GetSourcePath() . "/compilation_cache";
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
		return $this->GetFrameworkPath() . '/data';
	}

	public function GetTfpdfFontsPath()
	{
		return $this->GetFrameworkPath() . '/tfpdf/font/unifont';
	}

}
