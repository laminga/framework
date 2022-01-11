<?php

namespace minga\framework;

class CompressedDirectory
{
	public $path;
	public $expandedPath;
	private $expanded = false;
	private $file;

	public function __construct($path, $file = 'content.zip')
	{
		$this->path = $path;
		$this->file = $file;
	}

	public function Release() : void
	{
		if ($this->expanded == false)
			return;
		IO::RemoveDirectory($this->expandedPath);
		$this->expanded = false;
	}

	public function GetFilename()
	{
		return $this->path . '/' . $this->file;
	}

	public function IsCompressed()
	{
		return file_exists($this->GetFilename());
	}

	public function Compress()
	{
		if ($this->IsCompressed())
			return false;
		Profiling::BeginTimer();
		// Crea el zip
		if (IO::GetFilesCount($this->path) > 0)
		{
			$tmp = IO::GetTempFilename();
			$zip = new Zip($tmp);
			$target = $this->GetFilename();
			IO::Delete($target);
			$zip->AppendFilesToZip($this->path, "");
			// Lo mueve
			IO::Move($tmp, $target);
			// Vacía la carpeta
			foreach(IO::GetFiles($this->path) as $file)
			{
				if ($this->path . "/" . $file != $target)
					IO::Delete($this->path . "/" . $file);
			}
			$ret = true;
		}
		else
			$ret = false;
		Profiling::EndTimer();
		return $ret;
	}

	public function Expand() : void
	{
		if ($this->expanded)
			return;
		Profiling::BeginTimer();
		$zip = new \ZipArchive();
		$res = $zip->open($this->GetFilename());
		if ($res === true)
		{
			$this->expandedPath = IO::GetTempFilename();
			IO::EnsureExists($this->expandedPath);
			$zip->extractTo($this->expandedPath);
			$zip->close();
			$this->expanded = true;
		}
		else
			throw new ErrorException('Could not access contents.');

		Profiling::EndTimer();
	}
}
