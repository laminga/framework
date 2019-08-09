<?php

namespace minga\framework;

class IO
{
	private static $compressedDirectories;

	public static function AppendAllBytes($filename, $bytes)
	{
		//TODO: Agregar manejo de errores.
		$fp = fopen($filename, 'a');
		fwrite($fp, $bytes);
		fclose($fp);
	}

	public static function MoveDirectoryContents($dirsource, $target)
	{
		IO::EnsureExists($target);
		// limpia
		$dirname = substr($dirsource,strrpos($dirsource,"/")+1);
		if (file_exists($target . "/" . $dirname))
			IO::RemoveDirectory($target . "/" . $dirname);
		// copia
		IO::CopyDirectory($dirsource, $target);
		// borra
		IO::ClearDirectory($dirsource, true);
	}

	public static function ReadAllText($path, $maxLength = -1)
	{
		if ($maxLength == -1)
			return file_get_contents($path);
		else
			return file_get_contents($path, false, null, 0, $maxLength);
	}

	public static function GetDirectory($file)
	{
		$path_parts = pathinfo($file);
		return $path_parts['dirname'];
	}

	public static function GetDirectoryName($file)
	{
		return self::GetFilenameNoExtension(self::GetDirectory($file));
	}

	public static function GetFilenameNoExtension($file)
	{
		$path_parts = pathinfo($file);
		return $path_parts['filename'];
	}

	public static function GetUrlNoExtension($file)
	{
		$n = strrpos($file, '.');
		if ($n !== false && $n > 0)
			return substr($file, 0, $n);
		else
			return $file;
	}

	public static function GetRelativePath($folder)
	{
		$base = Context::Paths()->GetRoot();
		if (Str::StartsWith($folder, $base))
			return substr($folder, strlen($base));
		else
			return $folder;
	}

	public static function ReadText($path, $length)
	{
		//TODO: Agregar manejo de errores.
		$handle = fopen($path, "r");
		$contents = fread($handle, $length);
		fclose($handle);
		return $contents;
	}

	public static function ReadAllLines($path)
	{
		//TODO: Agregar manejo de errores.
		$handle = fopen($path, 'r');
		$ret = [];
		while (feof($handle) == false)
		{
			$currentLine = fgets($handle) ;
			$ret[] = $currentLine;
		}
		fclose($handle);
		return $ret;
	}

	public static function WriteAllText($file, $text)
	{
		$ret = file_put_contents($file, $text);
		Backup::AppendModified($file);
		return $ret;
	}

	public static function WriteJson($file, $text, $pretty = false)
	{
		$flags = 0;
		if($pretty)
			$flags = JSON_PRETTY_PRINT;
		return self::WriteAllText($file, json_encode($text, $flags));
	}

	public static function ReadFileChunked($filepath)
	{
		$handle = fopen($filepath, 'rb');
		if($handle === false)
			return false;

		while(feof($handle) == false)
		{
			$buffer = fread($handle, 1024 * 1024);
			echo $buffer;
			ob_flush();
			flush();
		}

		return fclose($handle);
	}

	public static function ReadJson($path)
	{
		$text = self::ReadAllText($path);
		return json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $text), true);
	}

	public static function AppendLine($file, $line)
	{
		$handle = fopen($file, 'a');
		if ($handle === false)
			return false;
		if (fwrite($handle, $line . "\r\n") === false)
		{
			fclose($handle);
			return false;
		}
		fclose($handle);
		Backup::AppendModified($file);
		return true;
	}

	public static function ReadTitleTextFile($file, &$title, &$text)
	{
		// formato
		$pStart = "<p style='margin: 6px 0px 6px 0px;'>";
		$pEnd = "</p>";

		$content = self::ReadAllText($file);

		// lee titulo
		$n = strpos($content, "\n");
		$title = $pStart . substr($content, 0, $n) . $pEnd;

		// lee resto
		$lines = explode("\\n", substr($content, $n + 1));
		$text = $pStart . implode($pEnd . $pStart, $lines) . $pEnd;
	}

	public static function ReadKeyValueCSVFile($path)
	{
		//TODO: Agregar manejo de errores.
		$fp = fopen($path, 'r');
		$ret = [];
		while (($data = fgetcsv($fp)) !== false)
		{
			if (sizeof($data) == 2)
				$ret[$data[0]] = $data[1];
		}
		fclose($fp);
		return $ret;
	}

	public static function WriteKeyValueCSVFile($file, $assoc_arr)
	{
		//TODO: Agregar manejo de errores.
		$fp = fopen($file, 'w');
		foreach ($assoc_arr as $key => $value)
			fputcsv($fp, [$key, $value]);
		fclose($fp);
		Backup::AppendModified($file);
	}

	public static function CompareFileSize($fileA, $fileB)
	{
		if (file_exists($fileA) == false || file_exists($fileB) == false)
			MessageBox::ThrowMessage("Archivo no encontrado para comparación binaria.");

		return (filesize($fileA) == filesize($fileB));

	}

	public static function CompareBinaryFile($fileA, $fileB)
	{
		if (file_exists($fileA) == false || file_exists($fileB) == false)
			MessageBox::ThrowMessage("Archivo no encontrado para comparación binaria.");

		if (filesize($fileA) == filesize($fileB))
		{
			//TODO: Agregar manejo de errores.
			$fpA = fopen($fileA, 'rb');
			$fpB = fopen($fileB, 'rb');

			while (($b = fread($fpA, 4096)) !== false)
			{
				$b_b = fread($fpB, 4096);
				if ($b !== $b_b)
				{
					fclose($fpA);
					fclose($fpB);
					return false;
				}
			}

			fclose($fpA);
			fclose($fpB);

			return true;
		}
		return false;
	}

	public static function ReadIniFile($path)
	{
		return parse_ini_file($path);
	}

	public static function ReadEscapedIniFile($path)
	{
		$attributes = parse_ini_file($path);
		foreach($attributes as $key => $value)
			$attributes[$key] = urldecode($value);
		return $attributes;
	}

	public static function ReadEscapedIniFileWithSections($path)
	{
		$attributes = parse_ini_file($path, true);
		foreach($attributes as &$values)
			foreach($values as $key => $value)
				$values[$key] = urldecode($value);
		return $attributes;
	}

	public static function WriteEscapedIniFileWithSections($file, $assoc_arr)
	{
		$content = "";
		foreach($assoc_arr as $section => $values)
			$content .= self::AssocArraySectionToString($section, $values);

		$directory = dirname($file);

		self::CreateDirectory($directory);

		$handle = fopen($file, 'w');
		if ($handle === false)
			return false;

		if (fwrite($handle, $content) === false)
		{
			fclose($handle);
			return false;
		}

		fclose($handle);
		Backup::AppendModified($file);
		return true;
	}

	public static function GetSectionFromIniFile($path, $section)
	{
		$sections = self::ReadEscapedIniFileWithSections($path);
		if (array_key_exists($section, $sections))
			return $sections[$section];
		else
			return null;
	}

	private static function AssocArraySectionToString($section, $assoc_arr)
	{
		$content = "[" . $section. "]\r\n";
		foreach($assoc_arr as $key => $value)
			$content .= $key. "=" . urlencode($value) . "\r\n";
		return $content;
	}

	public static function WriteIniFile($file, $assoc_arr)
	{
		$handle = fopen($file, 'w');
		if ($handle === false)
			return false;
		$content = "";
		foreach ($assoc_arr as $key => $elem)
			$content .= $key . '="' . $elem . "\"\r\n";

		if(fwrite($handle, $content) === false)
		{
			fclose($handle);
			return false;
		}

		fclose($handle);
		Backup::AppendModified($file);
		return true;
	}

	public static function WriteEscapedIniFile($file, $assoc_arr, $keepSections = false)
	{
		$directory = dirname($file);

		self::CreateDirectory($directory);

		// se fija si tiene que mantener secciones
		if ($keepSections && file_exists($file))
		{
			$sections = self::ReadEscapedIniFileWithSections($file);
			$sections['General'] = $assoc_arr;
			return self::WriteEscapedIniFileWithSections($file, $sections);
		}
		$content = self::AssocArraySectionToString('General', $assoc_arr);
		// empieza a grabar
		$handle = fopen($file, 'w');
		if ($handle === false)
			return false;
		if (fwrite($handle, $content) === false)
		{
			fclose($handle);
			return false;
		}

		fclose($handle);
		Backup::AppendModified($file);
		return true;
	}

	public static function RemoveExtension($filename)
	{
		$n = strrpos($filename, '.');
		if ($n === false || $n <= 0)
			return $filename;
		return substr($filename, 0, $n);
	}

	public static function EnsureExists($directory)
	{
		if (is_dir($directory) == false)
		{
			self::EnsureExists(dirname($directory));
			self::CreateDirectory($directory);
		}
	}

	public static function CreateDirectory($directory)
	{
		try
		{
			if (is_dir($directory) == false)
			{
				Backup::AppendDirectoryCreated($directory);
				mkdir($directory);
			}
		}
		catch (\Exception $e)
		{
			/* Este catch está porque incluso chequeando con if exists antes,
				pueda haber concurrencia entre if exists y mkdir, y en consecuencia
				sale el mkdir con error de 'directorio ya existe'. Salir con ese
				error no es útil, dado que el objetivo de este método es crear
				el directorio. Se podría generar un lock a nivel aplicación para
				hacer un if exist con lock, pero el beneficio es poco claro.
			 */
			if (is_dir($directory) == false)
				throw new \ErrorException('Could not create directory');
		}
	}

	public static function GetFilesCursor($path, $ext = '')
	{
		return new FilesCursor($path, $ext);
	}

	public static function GetDirectoriesCursor($path, $ext = '')
	{
		return new DirectoriesCursor($path, $ext);
	}

	public static function GetFilesFullPath($path, $ext = '')
	{
		return self::GetFiles($path, $ext, true);
	}

	public static function GetFiles($path, $ext = '', $returnFullPath = false)
	{
		return self::GetFilesStartsWithAndExt($path, '', $ext, $returnFullPath);
	}

	public static function GetFilesStartsWith($path, $start = '', $returnFullPath = false)
	{
		return self::GetFilesStartsWithAndExt($path, $start, '', $returnFullPath);
	}

	public static function GetFilesStartsWithAndExt($path, $start = '', $ext = '', $returnFullPath = false)
	{
		if($ext != '' && Str::StartsWith($ext, '.') == false)
			$ext = '.' . $ext;

		$start = str_replace(['/', "\\"], '', $start);

		$notAlpha = false;
		if($start == '@')
		{
			$start = '';
			$notAlpha = true;
		}

		$ret = glob($path . '/' . $start . '*'. $ext);

		if($notAlpha)
			$ret = preg_grep('/^' . preg_quote($path . '/', '/') . '[^a-zA-Z].*/', $ret);

		$ret = array_values(array_filter($ret, 'is_file'));

		if ($returnFullPath)
			return $ret;

		//remueve directorio base
		return preg_replace('/^' . preg_quote($path . '/', '/') . '/', '', $ret);
	}

	public static function HasFiles($path, $ext = '')
	{
		if ($handle = self::OpenDirNoWarning($path))
		{
			while (false !== ($entry = readdir($handle)))
			{
				if (($ext == '' || Str::EndsWith($entry, $ext)) &&
					$entry != '..' && $entry != '.' && is_file($path . '/'. $entry))
				{
					closedir($handle);
					return true;
				}
			}
			closedir($handle);
		}
		return false;
	}

	public static function GetFilesCount($path, $ext = '')
	{
		return count(self::GetFilesFullPath($path, $ext));
	}

	public static function GetDirectories($path, $start = '', $returnFullPath = false)
	{
		$start = str_replace(['/', "\\"], '', $start);

		$notAlpha = false;
		if($start == '@')
		{
			$start = '';
			$notAlpha = true;
		}

		$ret = glob($path . '/' . $start . '*', GLOB_ONLYDIR);

		if($notAlpha)
			$ret = preg_grep('/^' . preg_quote($path . '/', '/') . '[^a-zA-Z].*/', $ret);

		if($returnFullPath)
			return $ret;

		return preg_replace('/^' . preg_quote($path . '/', '/') . '/', '', $ret);
	}

	public static function GetSequenceName($file, $index, $numLength = 5)
	{
		$i = sprintf('%0' . (int)$numLength . 'd', $index);
		$info = pathinfo($file);
		return $info['dirname'] . '/' . $info['filename'] . '_' . $i . '.' . $info['extension'];
	}

	public static function ClearDirectory($dir, $recursive = false)
	{
		if (file_exists($dir) == false)
			return 0;
		$n = 0;
		$files = self::GetFiles($dir);
		foreach($files as $file)
		{
			self::Delete($dir . '/' . $file);
			$n++;
		}
		if ($recursive)
		{
			foreach(self::GetDirectories($dir) as $subdir)
				$n += self::RemoveDirectory($dir . '/' . $subdir);
		}
		return $n;
	}

	public static function ClearFiles($dir, $extension, $recursive = false, $showOnly = false)
	{
		if (file_exists($dir) == false)
			return 0;
		$n = 0;
		$files = self::GetFiles($dir, "." . $extension);
		foreach($files as $file)
		{
			if ($showOnly)
				echo($dir . '/' . $file . '<br>');
			else
				self::Delete($dir . '/' . $file);
			$n++;
		}
		if ($recursive)
			foreach(self::GetDirectories($dir) as $subdir)
				$n += self::ClearFiles($dir . '/' . $subdir, $extension, true, $showOnly);
		return $n;
	}

	public static function FileMTime($file)
	{
		try
		{
			return filemtime($file);
		}
		catch(\Exception $e)
		{
			if($e->getCode() !== E_WARNING)
				Log::HandleSilentException($e);
		}
		return false;
	}

	public static function MoveDirectory($dirSource, $dirDest, $dirName = "", $exclusions = null, $timeFrom = null, $createEmptyFolders = true)
	{
		self::CopyDirectory($dirSource, $dirDest, $dirName, $exclusions, $timeFrom, $createEmptyFolders);
		self::RemoveDirectory($dirSource);
	}

	public static function CopyDirectory($dirSource, $dirDest, $dirName = "", $exclusions = null, $timeFrom = null, $createEmptyFolders = true, $excludedExtension = '')
	{
		// se fija si el source está excluido
		if ($exclusions != null)
		{
			$exclusionsFull = [];
			foreach($exclusions as $exclusion)
				$exclusionsFull[] = $dirSource . "/" . $exclusion;
			$exclusions = $exclusionsFull;
		}
		return self::doCopyDirectory($dirSource, $dirDest, $dirName, $exclusions, $timeFrom, $createEmptyFolders, $excludedExtension);
	}

	public static function CopyFiles($dirSource, $dirDest, $exclusions = null, $timeFrom = null)
	{
		// se fija si el source está excluido
		if ($exclusions != null)
		{
			$exclusionsFull = [];
			foreach($exclusions as $exclusion)
				$exclusionsFull[] = $dirSource . "/" . $exclusion;
			$exclusions = $exclusionsFull;
		}
		$dirHandle = self::OpenDirNoWarning($dirSource);

		while($file = readdir($dirHandle))
		{
			if($file != '.' && $file != '..'
				&& is_dir($dirSource . '/' . $file) == false
				&& ($timeFrom == null
					|| self::FileMTime($dirSource . '/' . $file) >= $timeFrom))
			{
				$target = $dirDest . '/' . $file;
				copy($dirSource . '/' . $file, $target);
				Backup::AppendModified($target);
			}
		}
		closedir($dirHandle);
		return true;
	}

	private static function doCopyDirectory($dirSource, $dirDest, $dirName, $exclusions, $timeFrom, $createEmptyFolders, $excludedExtension ='')
	{
		// se fija si el source está excluido
		if ($exclusions != null)
		{
			foreach($exclusions as $exclusion)
			{
				if ($exclusion == $dirSource)
					return;
			}
		}

		// recursive function to copy all subdirectories and contents
		$dirHandle = null;
		if(is_dir($dirSource))
			$dirHandle = self::OpenDirNoWarning($dirSource);
		if ($dirName == '')
			$dirName = substr($dirSource, strrpos($dirSource, '/') + 1);

		if ($createEmptyFolders)
		{
			self::EnsureExists($dirDest);
			mkdir($dirDest . '/' . $dirName, 0750);
		}

		while($file = readdir($dirHandle))
		{
			if($file != '.' && $file != '..')
			{
				if(is_dir($dirSource . '/' . $file) == false)
				{
					if (($timeFrom == null || self::FileMTime($dirSource . '/' . $file) >= $timeFrom)
						&& ($excludedExtension == '' || Str::EndsWith($file, '.' . $excludedExtension) == false))
					{
						if ($createEmptyFolders == false)
							self::EnsureExists($dirDest . '/' . $dirName);
						$target = $dirDest . '/' . $dirName . '/' . $file;
						copy($dirSource . '/' . $file, $target);
						Backup::AppendModified($target);
					}
				}
				else
				{
					$dirdest1 = $dirDest . '/' . $dirName;
					self::doCopyDirectory($dirSource . '/' . $file, $dirdest1, '', $exclusions, $timeFrom, $createEmptyFolders, $excludedExtension);
				}
			}
		}
		closedir($dirHandle);
		return true;
	}

	/**
	 * Remueve directorio completo aunque
	 * contenga archivos.
	 */
	public static function RemoveDirectory($dir)
	{
		if (file_exists($dir) == false)
			return 0;
		if(is_file($dir))
		{
			self::Delete($dir);
			return 1;
		}
		$n = 0;
		if($dh = self::OpenDirNoWarning($dir))
		{
			while(($file = readdir($dh)) !== false)
			{
				if($file == '.' || $file == '..')
					continue;
				$n += self::RemoveDirectory($dir . '/' . $file);
			}
			closedir($dh);
			self::RmDir($dir);
		}
		return $n;
	}

	/**
	 * Wrapper de función rmdir de php,
	 * para evitar warnings.
	 * Sólo borra directorios vacíos.
	 */
	public static function RmDir($dir)
	{
		try
		{
			if(file_exists($dir))
			{
				Backup::AppendDirectoryDeleted($dir);
				return rmdir($dir);
			}
		}
		catch(\Exception $e)
		{
			if($e->getCode() !== E_WARNING)
				Log::HandleSilentException($e);
		}
		return false;
	}

	public static function OpenDirNoWarning($dir)
	{
		try
		{
			if(file_exists($dir))
				return opendir($dir);
		}
		catch(\Exception $e)
		{
			if($e->getCode() !== E_WARNING)
				Log::HandleSilentException($e);
		}
		return false;
	}

	public static function GetDirectoryINodesCount($dir)
	{
		Profiling::BeginTimer();
		$ret = System::RunCommandRaw('find ' . $dir . '/. | wc -l');
		Profiling::EndTimer();
		return $ret['lastLine'];
	}

	public static function GetDirectorySizeUnix($dir)
	{
		try
		{
			Profiling::BeginTimer();
			$ret = System::RunCommandRaw('/usr/bin/du -sb ' . $dir);
			$pos = strpos($ret['lastLine'], "\t");
			if($pos === false)
				return 0;
			return substr($ret['lastLine'], 0, $pos);
		}
		finally
		{
			Profiling::EndTimer();
		}
	}

	public static function GetDirectorySize($dir, $sizeOnly = false)
	{
		Profiling::BeginTimer();
		if(System::IsOnIIS())
			$ret = self::GetDirectorySizeWin($dir);
		else
		{
			$ret = ['size' => self::GetDirectorySizeUnix($dir)];
			if ($sizeOnly == false)
				$ret['inodes'] = self::GetDirectoryINodesCount($dir);
		}
		Profiling::EndTimer();
		return $ret;
	}

	private static function GetDirectorySizeWin($dir)
	{
		if(($dh = self::OpenDirNoWarning($dir)) == false)
		{
			return ['size' => 0, 'inodes' => 0];
		}

		$size = 0;
		$n = 0;
		$inodes = 1;
		while(($file = readdir($dh)) !== false)
		{
			if($file !== '.' && $file !== '..')
			{
				$item = $dir . '/' . $file;
				if (is_file($item))
				{
					$size += filesize($item);
					$inodes++;
					$n++;
				}
				else
				{
					$data = self::GetDirectorySizeWin($item);
					$size += $data['size'];
					$inodes += $data['inodes'];
				}
			}
		}
		closedir($dh);

		return ['size' => $size, 'inodes' => $inodes];
	}

	public static function SendFilesToZip($zipFile, $files, $sourcefolder)
	{
		self::Delete($zipFile);
		$zip = new \ZipArchive();
		if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true)
			throw new ErrorException("Could not open archive");

		// adds files to the file list
		$sourcefolder = str_replace("\\", "/", $sourcefolder);
		if (Str::EndsWith($sourcefolder, "/") == false)
			$sourcefolder .= "/";
		foreach ($files as $file)
		{
			//fix archive paths
			$fileFixed = str_replace("\\", "/", $file);
			$path = str_replace($sourcefolder, "", $fileFixed); //remove the source path from the $key to return only the file-folder structure from the root of the source folder

			if (file_exists($file) == false)
				throw new ErrorException('file does not exist. Please contact your administrator or try again later.');
			if (is_readable($file) == false)
				throw new ErrorException('file not readable. Please contact your administrator or try again later.');

			if($zip->addFromString($path, $file) == false)
				throw new ErrorException("ERROR: Could not add file: ... </br> numFile:");
			if($zip->addFile(realpath($file), $path) == false)
				throw new ErrorException("ERROR: Could not add file: ... </br> numFile:");
		}
		// closes the archive
		$zip->close();
		Backup::AppendModified($zipFile);
	}

	public static function GetTempFilename()
	{
		$path = Context::Paths()->GetTempPath();
		self::EnsureExists($path);
		$name = tempnam($path, "");
		self::Delete($name);
		return $name;
	}

	public static function Copy($source, $target)
	{
		Profiling::BeginTimer();

		Backup::AppendModified($target);
		copy($source, $target);

		Profiling::EndTimer();
	}

	public static function Move($source, $target)
	{
		try
		{
			if(file_exists($source))
			{
				Backup::AppendDeleted($source);
				Backup::AppendModified($target);
				$ret = rename($source, $target);
				return $ret;
			}
		}
		catch(\Exception $e)
		{
			if($e->getCode() !== E_WARNING)
				Log::HandleSilentException($e);
		}
		return false;
	}

	public static function IsCompressedDirectory($path)
	{
		$dir = new CompressedDirectory($path);
		$simple = $dir->IsCompressed();
		if ($simple)
			return true;
		$dir = new CompressedInParentDirectory($path);
		return $dir->IsCompressed();
	}

	public static function GetCompressedDirectory($path)
	{
		if (self::$compressedDirectories != null)
		{
			foreach(self::$compressedDirectories as $folder)
			{
				if ($folder->path == $path)
					return $folder;
			}
		}
		$dir = new CompressedDirectory($path);
		if ($dir->IsCompressed() == false)
			$dir = new CompressedInParentDirectory($path);

		if (self::$compressedDirectories == null)
			self::$compressedDirectories = [];
		self::$compressedDirectories[] = $dir;
		return $dir;
	}

	public static function ReleaseCompressedDirectories()
	{
		if (self::$compressedDirectories != null)
		{
			foreach(self::$compressedDirectories as $folder)
				$folder->Release();
		}
	}

	public static function Delete($file)
	{
		try
		{
			if (file_exists($file))
			{
				Backup::AppendDeleted($file);
				return unlink($file);
			}
		}
		catch(\Exception $e)
		{
			if($e->getCode() !== E_WARNING)
				Log::HandleSilentException($e);
		}
		return false;
	}

	public static function Exists($file)
	{
		return file_exists($file);
	}
}
