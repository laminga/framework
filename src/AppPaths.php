<?php

namespace minga\framework;

class AppPaths
{
	public static $useStorageFromRoot = false;

	public function GetRoot()
	{
		return Context::Settings()->rootPath;
	}

	public function GetBinPath()
	{
		return realpath($this->GetRoot() . "/cgi-bin");
	}

	public function GetStorageRoot()
	{
		if (self::$useStorageFromRoot)
			// esto es para zafar que no me se me rompa lo de mapas
			return realpath($this->GetRoot() . "/storage");
		else
			return realpath($this->GetRoot() . "/../storage");
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
		return $this->GetFrameworkPath() . '/data';
	}

	public function GetTfpdfFontsPath()
	{
		//TODO: borrar esto y la llamada. Revisar cache actual en vendor...
		return $this->GetFrameworkPath() . '/tfpdf/font/unifont';
	}

}
