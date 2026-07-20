<?php

namespace minga\framework;

class CompressedInParentDirectory extends CompressedDirectoryBase
{
	private string $dirName;

	public function __construct(string $path, string $file = 'content.zip')
	{
		parent::__construct($path, $file);
		$this->dirName = IO::GetFilenameNoExtension($path);
	}

	public function GetFilename() : string
	{
		return dirname($this->path) . '/' . $this->file;
	}

	public function IsCompressed() : bool
	{
		if (file_exists($this->GetFilename()) == false)
			return false;

		Profiling::BeginTimer();
		try
		{
			$zip = new ZipArchiveExtended();
			$res = $zip->open($this->GetFilename());
			if ($res !== true)
				throw new ErrorException(Context::Trans('No se pudo acceder a los contenidos.'));

			$hasSubdir = $zip->hasSubdir($this->dirName);
			$zip->close();
			return $hasSubdir;
		}
		finally
		{
			Profiling::EndTimer();
		}
	}

	public function Compress() : bool
	{
		if ($this->IsCompressed())
			return false;

		Profiling::BeginTimer();
		try
		{
			if (IO::GetFilesCount($this->path) == 0)
				return false;

			$useTemp = (file_exists($this->GetFilename()) == false);
			if ($useTemp)
				$tmp = IO::GetTempFilename();
			else
				$tmp = $this->GetFilename();

			$files = [];
			$sources = [];
			foreach(IO::GetFiles($this->path) as $file)
			{
				$files[] = $this->dirName . '/' . $file;
				$sources[] = $this->path . '/' . $file;
			}
			Zipping::AddOrUpdate($tmp, $files, $sources);
			if ($useTemp)
			{
				$target = $this->GetFilename();
				IO::Delete($target);
				IO::Move($tmp, $target);
			}
			IO::RemoveDirectory($this->path);
			return true;
		}
		finally
		{
			Profiling::EndTimer();
		}
	}

	public function Expand() : void
	{
		if ($this->expanded)
			return;
		Profiling::BeginTimer();
		$zip = new ZipArchiveExtended();
		$res = $zip->open($this->GetFilename());
		if ($res !== true)
			throw new ErrorException(Context::Trans('No se pudo acceder a los contenidos.'));

		$this->expandedPath = IO::GetTempFilename();
		IO::EnsureExists($this->expandedPath);
		$zip->extractSubdirTo($this->expandedPath, $this->dirName);
		$zip->close();
		$this->expanded = true;
		Profiling::EndTimer();
	}
}
