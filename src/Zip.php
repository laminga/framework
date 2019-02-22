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

	private function OpenCreate()
	{
		$zip = new \ZipArchive();
		if (file_exists($this->targetFile) == false)
		{
			if ($zip->open($this->targetFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true)
				throw new \Exception('Could not open archive');
		}
		else if ($zip->open($this->targetFile) !== true)
			throw new \Exception('Could not open archive');
		return $zip;
	}

	public function AppendFilesToZipRecursive($basePath, array $relativePathsToZip, $ext = '')
	{
		$zip = $this->OpenCreate();

		$basePath = str_replace("\\", '/', $basePath);
		if (Str::EndsWith($basePath, '/') == false)
			$basePath .= '/';

		foreach($relativePathsToZip as $relPath)
		{
			$fullPath = realpath($basePath . $relPath);
			if(is_dir($fullPath))
			{
				$files = new \RecursiveIteratorIterator(
					new \RecursiveDirectoryIterator($fullPath,
					\RecursiveDirectoryIterator::CURRENT_AS_PATHNAME | \RecursiveDirectoryIterator::SKIP_DOTS));
			}
			else
				$files = [$fullPath];

			foreach($files as $file)
			{
				if($ext != '' && Str::EndsWith($file, $ext) == false)
					continue;

				$file = str_replace("\\", '/', $file);
				$relFile = str_replace($basePath, '', $file);

				if($zip->addFile($file, $relFile) == false)
					throw new \Exception('Could not add file.');
			}
		}
		$zip->close();
	}

	public function AppendFilesToZip($basePath, $relativePathToZip, $ext = '')
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
		$currentfiles = [];
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

	public function AppendFilesToZipRecursiveDeletting($basePath, array $relativePathsToZip, $ext, $bytesLimit, &$currentBytes)
	{
		$zip = $this->OpenCreate();

		$basePath = str_replace("\\", '/', $basePath);
		if (Str::EndsWith($basePath, '/') == false)
			$basePath .= '/';

		$currentfiles = [];
		foreach($relativePathsToZip as $relPath)
		{
			$fullPath = realpath($basePath . $relPath);
			if(is_dir($fullPath))
			{
				$files = new \RecursiveIteratorIterator(
					new \RecursiveDirectoryIterator($fullPath,
					\RecursiveDirectoryIterator::CURRENT_AS_PATHNAME | \RecursiveDirectoryIterator::SKIP_DOTS));
			}
			else
				$files = [$fullPath];

			foreach($files as $file)
			{
				if($ext != '' && Str::EndsWith($file, $ext) == false)
					continue;

				$file = str_replace("\\", '/', $file);
				$relFile = str_replace($basePath, '', $file);

				$currentBytes += filesize($file);
				$currentfiles[] = $file;
				if ($currentBytes >= $bytesLimit)
					break;

				if($zip->addFile($file, $relFile) == false)
					throw new \Exception('Could not add file.');
			}
			if ($currentBytes >= $bytesLimit)
				break;
		}
		$zip->close();

		foreach($currentfiles as $file)
			IO::Delete($file);

		return ($currentBytes <= $bytesLimit);
	}

	private function AddFolderToPath(array $files, $path)
	{
		$ret = [];
		foreach($files as $file)
			$ret[] = $path . '/' . $file;

		return $ret;
	}

	public function AddToZip($sourcefolder, array $files)
	{
		$sourcefolder = str_replace("\\", '/', $sourcefolder);
		if (Str::EndsWith($sourcefolder, '/') == false)
			$sourcefolder .= '/';

		$zip = $this->OpenCreate();

		// adds files to the file list
		for($n = 0; $n < count($files); $n++)
		{
			$file = $files[$n];
			//fix archive paths
			$fileFixed = str_replace("\\", '/', $file);

			//remove the source path from the $key to return only the
			//file-folder structure from the root of the source folder
			$path = str_replace($sourcefolder, '', $fileFixed);

			if (file_exists(realpath($file)) == false)
				throw new \Exception(realpath($file).' does not exist.');

			//if (is_readable($file) == false) throw new \Exception($file.' not readable.');
			if($zip->addFile(realpath($file), $path) == false)
				throw new \Exception('ERROR: Could not add file: ... </br> numFile:');
		}
		// closes the archive
		$zip->close();
	}

	public function Extract($path)
	{
		$zip = new \ZipArchive();

		if ($zip->open($this->targetFile) !== true)
			throw new \Exception('Failed to extract files: ');

		$ret = $zip->numFiles;
		$zip->extractTo($path);
		$zip->close();
		return $ret;
	}

	public function ExtractWithDates($path)
	{
		$zip = new \ZipArchive();
		if ($zip->open($this->targetFile) !== true)
			throw new \Exception('Failed to extract files');

		$ret = $zip->numFiles;
		for($i = 0; $i < $zip->numFiles; $i++)
		{
			// extre
			$filename = $zip->getNameIndex($i);
			// cambiando hacia copy por performance (x100 a x1)
			//$zip->extractTo($path, $filename);
			copy('zip://' . $this->targetFile . '#' . $filename, $path . '/' . $filename);
			// pone fecha
			$stat = $zip->statIndex($i);
			if($stat === false)
				throw new \Exception('Failed to extract files');
			$mtime = intval($stat['mtime']);
			$extracted = $path . '/' . $filename;
			touch($extracted, $mtime, time());
		}
		$zip->close();
		return $ret;
	}

}
