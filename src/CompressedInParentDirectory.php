<?php

namespace minga\framework;


class CompressedInParentDirectory
{
	public $path;
	public $expandedPath;
	private $expanded = false;
	private $dirName;
	private $file;

	public function __construct($path, $file = 'content.zip')
	{
		$this->path = $path;
		$this->dirName = IO::GetFilenameNoExtension($path);
		$this->file = $file;
	}

	public function Release()
	{
		if ($this->expanded == false)
			return;
		IO::RemoveDirectory($this->expandedPath);
		$this->expanded = false;
	}
	public function GetFilename()
	{
		return dirname($this->path) . '/' . $this->file;
	}
	public function IsCompressed()
	{
		if (!file_exists($this->GetFilename()))
		{
			return false;
		}
		Profiling::BeginTimer();
		$zip = new ZipArchiveExtended();
		$res = $zip->open($this->GetFilename());
		if ($res === true)
		{
			$hasSubdir = $zip->hasSubdir($this->dirName);
			$zip->close();
		}
		else
		{
			Profiling::EndTimer();
			throw new ErrorException('Could not access contents.');
		}
		Profiling::EndTimer();
		return $hasSubdir;
	}

	public function Compress()
	{
		if ($this->IsCompressed())
			return false;
		Profiling::BeginTimer();
		// Crea el zip
		if (IO::GetFilesCount($this->path) > 0)
		{
			$useTemp = (file_exists($this->GetFilename()) == false);
			if ($useTemp)
				$tmp = IO::GetTempFilename();
			else
				$tmp = $this->GetFilename();

			$_ = new Zip($tmp);
			$files = [];
			$sources = [];
			foreach(IO::GetFiles($this->path) as $file)
			{
				$files[] = $this->dirName . '/' . $file;
				$sources[] = $this->path . '/' . $file;
			}
			Zipping::AddOrUpdate($tmp, $files, $sources);
			// Lo mueve
			if ($useTemp)
			{
				$target = $this->GetFilename();
				IO::Delete($target);
				IO::Move($tmp, $target);
			}
			// Vacía la carpeta
			IO::RemoveDirectory($this->path);
			$ret = true;
		}
		else
			$ret = false;

		Profiling::EndTimer();
		return $ret;
	}

	public function Expand()
	{
		if ($this->expanded)
			return;
		Profiling::BeginTimer();
		$zip = new ZipArchiveExtended();
		$res = $zip->open($this->GetFilename());
		if ($res === true)
		{
			$this->expandedPath = IO::GetTempFilename();
			IO::EnsureExists($this->expandedPath);
			$zip->extractSubdirTo($this->expandedPath, $this->dirName);
			$zip->close();
			$this->expanded = true;
		}
		else
		{
			throw new ErrorException('Could not access contents.');
		}

		Profiling::EndTimer();
	}
}
