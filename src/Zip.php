<?php

namespace minga\framework;

use minga\framework\locking\ZipLock;

class Zip
{
	public $targetFile;
	public $lock;
	public function __construct($file)
	{
		$this->targetFile = $file;
		$this->lock = new ZipLock($file);
	}

	public function AppendFilesToZip($basePath, $relativePathToZip, $ext = "")
	{
		$myfiles = IO::GetFiles($basePath . $relativePathToZip, $ext);
		$myfiles = $this->AddFolderToPath($myfiles, $basePath . $relativePathToZip);
		$this->AddToZip($basePath, $myfiles);
	}

	public function AppendFilesToZipDeletting($basePath, $relativePathToZip, $ext, $bytesLimit, &$currentBytes)
	{
		$myfiles = IO::GetFiles($basePath . $relativePathToZip, $ext);
		$myfiles = $this->AddFolderToPath($myfiles, $basePath . $relativePathToZip);
		// create myfiles filtered
		$currentfiles = array();
		//
		foreach($myfiles as $file)
		{
			$currentBytes += filesize($file);
			$currentfiles[] = $file;
			if ($currentBytes > $bytesLimit)
				break;
		}

		$this->AddToZip($basePath, $currentfiles);
		// delete files
		foreach($currentfiles as $file)
			IO::Delete($file);

		return ($currentBytes <= $bytesLimit);
	}

	public function AppendFilesToZipRecursiveDeletting($basePath, $relativePathToZip, $ext, $bytesLimit, &$currentBytes)
	{
		if ($this->AppendFilesToZipDeletting($basePath, $relativePathToZip, $ext, $bytesLimit, $currentBytes) == false)
			return false;
		foreach(IO::GetDirectories($basePath . $relativePathToZip) as $folder)
			if ($this->AppendFilesToZipRecursiveDeletting($basePath, $relativePathToZip . "/" . $folder, $ext, $bytesLimit, $currentBytes) == false)
				return false;
		return true;
	}

	public function AppendFilesToZipRecursive($basePath, $relativePathToZip, $ext = "")
	{
		$this->AppendFilesToZip($basePath, $relativePathToZip, $ext);
		foreach(IO::GetDirectories($basePath . $relativePathToZip) as $folder)
			$this->AppendFilesToZipRecursive($basePath, $relativePathToZip . "/" . $folder, $ext);
	}

	private function AddFolderToPath($files, $path)
	{
		$ret = array();
		foreach($files as $file)
		{
			$ret[] = $path . "/" . $file;
		}
		return $ret;
	}

	public function AddToZip($sourcefolder, $files)
	{
		$sourcefolder = str_replace("\\", "/", $sourcefolder);
		if (Str::EndsWith($sourcefolder, "/") == false) $sourcefolder.= "/";

		$zip = new \ZipArchive();
		// This creates and then gives the option to save the zip file
		if (!file_exists($this->targetFile))
		{
			if ($zip->open($this->targetFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true)
				throw new \Exception ("Could not open archive");
		}
		else
		{
			if ($zip->open($this->targetFile) !== true)
				throw new \Exception ("Could not open archive");
		}

		// adds files to the file list
		for($n = 0; $n < sizeof($files); $n++)
		{
			$file = $files[$n];
			//fix archive paths
			$fileFixed = str_replace("\\", "/", $file);

			//remove the source path from the $key to return only the
			//file-folder structure from the root of the source folder
			$path = str_replace($sourcefolder, "", $fileFixed);

			if (!file_exists(realpath($file)))
				throw new \Exception(realpath($file).' does not exist.');

			//if (!is_readable($file)) { throw new \Exception($file.' not readable.'); }
			if($zip->addFile(realpath($file), $path) == false)
				throw new \Exception ("ERROR: Could not add file: ... </br> numFile:");
		}
		// closes the archive
		$zip->close();
	}

	public function Extract($path)
	{
		$zip = new \ZipArchive();
		if ($zip->open($this->targetFile) === true)
		{
			$ret = $zip->numFiles;
			$zip->extractTo($path);
			$zip->close();
			return $ret;
		}
		else
		{
			throw new \Exception("Failed to extract files: ");
		}
	}

	public function ExtractWithDates($path)
	{
		$zip = new \ZipArchive();
		if ($zip->open($this->targetFile) === true)
		{
			$ret = $zip->numFiles;
			for($i = 0; $i < $zip->numFiles; $i++) {
				// extre
				$filename = $zip->getNameIndex($i);
				// cambiando hacia copy por performance (x100 a x1)
				//$zip->extractTo($path, $filename);
				copy("zip://" . $this->targetFile . "#" . $filename, $path . "/" . $filename);
				// pone fecha
				$stat = $zip->statIndex($i);
				if($stat === false)
					throw new \Exception ("Failed to extract files");
				$mtime = intval($stat['mtime']);
				$extracted = $path . "/" . $filename;
				touch($extracted, $mtime, time());
			}
			$zip->close();
			return $ret;
		}
		else
		{
			throw new \Exception("Failed to extract files");
		}
	}

}
