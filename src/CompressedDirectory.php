<?php

namespace minga\framework;

class CompressedDirectory extends CompressedDirectoryBase
{
	public function GetFilename() : string
	{
		return $this->path . '/' . $this->file;
	}

	public function IsCompressed() : bool
	{
		return file_exists($this->GetFilename());
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

			$tmp = IO::GetTempFilename();
			$zip = new Zip($tmp);
			$target = $this->GetFilename();
			IO::Delete($target);
			$zip->AppendFilesToZip($this->path, "");
			IO::Move($tmp, $target);
			foreach(IO::GetFiles($this->path) as $file)
			{
				if ($this->path . "/" . $file != $target)
					IO::Delete($this->path . "/" . $file);
			}
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
		$zip = new \ZipArchive();
		$res = $zip->open($this->GetFilename());
		if ($res !== true)
			throw new ErrorException(Context::Trans('No se pudo acceder a los contenidos.'));

		$this->expandedPath = IO::GetTempFilename();
		IO::EnsureExists($this->expandedPath);
		$zip->extractTo($this->expandedPath);
		$zip->close();
		$this->expanded = true;
		Profiling::EndTimer();
	}
}
