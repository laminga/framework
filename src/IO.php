<?php

namespace minga\framework;

class IO
{
	private static $compressedDirectories;

	public static function AppendAllBytes($filename, $bytes) : void
	{
		$fp = fopen($filename, 'a');
		fwrite($fp, $bytes);
		fclose($fp);
	}

	public static function MoveDirectoryContents($dirsource, $target) : void
	{
		IO::EnsureExists($target);
		// limpia
		$dirname = substr($dirsource, strrpos($dirsource, "/") + 1);
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
		return file_get_contents($path, false, null, 0, $maxLength);
	}

	public static function GetDirectory($file)
	{
		$pathParts = pathinfo($file);
		return $pathParts['dirname'];
	}

	public static function GetDirectoryName($file)
	{
		return self::GetFilenameNoExtension(self::GetDirectory($file));
	}

	public static function GetFileExtension($file)
	{
		return pathinfo($file, PATHINFO_EXTENSION);
	}

	public static function GetFilenameNoExtension($file)
	{
		$pathParts = pathinfo($file);
		return $pathParts['filename'];
	}

	public static function GetUrlNoExtension($file)
	{
		$n = strrpos($file, '.');
		if ($n !== false && $n > 0)
			return substr($file, 0, $n);

		return $file;
	}

	public static function GetRelativePath($folder)
	{
		$base = Context::Paths()->GetRoot();
		if (Str::StartsWith($folder, $base))
			return substr($folder, strlen($base));
		return $folder;
	}

	public static function ReadText($path, $length)
	{
		$handle = fopen($path, "r");
		$contents = fread($handle, $length);
		fclose($handle);
		return $contents;
	}

	public static function ReadAllBytes($path)
	{
		return file_get_contents($path);
	}

	public static function ReadAllLines($path, $maxLines = null)
	{
		$handle = fopen($path, 'r');
		$ret = [];
		$i = 0;
		while (feof($handle) == false)
		{
			$ret[] = fgets($handle);
			if($maxLines !== null && ++$i >= $maxLines)
				break;
		}
		fclose($handle);
		return $ret;
	}

	public static function WriteAllText($path, $text)
	{
		return file_put_contents($path, $text);
	}

	public static function WriteJson($path, $data, $pretty = false)
	{
		$flags = JSON_INVALID_UTF8_SUBSTITUTE;
		if($pretty)
			$flags |= JSON_PRETTY_PRINT;

		$json = json_encode($data, $flags);
		if($json === false)
			throw new \ErrorException('Error al crear json.');
		return self::WriteAllText($path, $json);
	}

	public static function ReadFileChunked($filepath) : bool
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

	public static function TryReadJson(string $path, &$ret) : bool
	{
		try
		{
			$ret = null;
			$ret = self::ReadJson($path);
			return true;
		}
		catch(\Exception $e)
		{
			return false;
		}
	}

	public static function ReadJson(string $path)
	{
		Profiling::BeginTimer();
		$text = self::ReadAllText($path);
		$ret = json_decode($text, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
		if($ret === null && Str::ToLower($text) != 'null')
			throw new \ErrorException('Error al leer json.');
		Profiling::EndTimer();
		return $ret;
	}

	public static function AppendLine($path, $line) : bool
	{
		$handle = fopen($path, 'a');
		if ($handle === false)
			return false;
		if (fwrite($handle, $line . "\r\n") === false)
		{
			fclose($handle);
			return false;
		}
		fclose($handle);
		return true;
	}

	public static function ReadTitleTextFile($file, &$title, &$text) : void
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

	public static function ReadKeyValueCSVFile(string $path) : array
	{
		$fp = fopen($path, 'r');
		$ret = [];
		while (($data = fgetcsv($fp)) !== false)
		{
			if (count($data) == 2)
				$ret[$data[0]] = $data[1];
		}
		fclose($fp);
		return $ret;
	}

	public static function WriteKeyValueCSVFile($path, $assocArr) : void
	{
		$fp = fopen($path, 'w');
		foreach ($assocArr as $key => $value)
			fputcsv($fp, [$key, $value]);
		fclose($fp);
	}

	public static function CompareFileSize($fileA, $fileB) : bool
	{
		if (file_exists($fileA) == false || file_exists($fileB) == false)
			MessageBox::ThrowMessage("Archivo no encontrado para comparación binaria.");

		return filesize($fileA) == filesize($fileB);

	}

	public static function CompareBinaryFile($fileA, $fileB) : bool
	{
		if (file_exists($fileA) == false || file_exists($fileB) == false)
			MessageBox::ThrowMessage("Archivo no encontrado para comparación binaria.");

		if (filesize($fileA) == filesize($fileB))
		{
			$fpA = fopen($fileA, 'rb');
			$fpB = fopen($fileB, 'rb');

			while (($b = fread($fpA, 4096)) !== false)
			{
				$bb = fread($fpB, 4096);
				if ($b !== $bb)
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

	public static function WriteEscapedIniFileWithSections($path, $assocArr) : bool
	{
		$content = "";
		foreach($assocArr as $section => $values)
			$content .= self::AssocArraySectionToString($section, $values);

		$directory = dirname($path);

		self::CreateDirectory($directory);

		$handle = fopen($path, 'w');
		if ($handle === false)
			return false;

		if (fwrite($handle, $content) === false)
		{
			fclose($handle);
			return false;
		}

		fclose($handle);
		return true;
	}

	public static function GetSectionFromIniFile(string $path, string $section)
	{
		$sections = self::ReadEscapedIniFileWithSections($path);
		if (array_key_exists($section, $sections))
			return $sections[$section];
		return null;
	}

	private static function AssocArraySectionToString(string $section, array $assocArr) : string
	{
		$content = "[" . $section . "]\r\n";
		foreach($assocArr as $key => $value)
			$content .= $key . "=" . urlencode($value) . "\r\n";
		return $content;
	}

	public static function WriteIniFile($path, $assocArr) : bool
	{
		$handle = fopen($path, 'w');
		if ($handle === false)
			return false;
		$content = "";
		foreach ($assocArr as $key => $elem)
			$content .= $key . '="' . $elem . "\"\r\n";

		if(fwrite($handle, $content) === false)
		{
			fclose($handle);
			return false;
		}

		fclose($handle);
		return true;
	}

	public static function WriteEscapedIniFile($path, array $assocArr, bool $keepSections = false) : bool
	{
		$directory = dirname($path);

		self::CreateDirectory($directory);

		// se fija si tiene que mantener secciones
		if ($keepSections && file_exists($path))
		{
			$sections = self::ReadEscapedIniFileWithSections($path);
			$sections['General'] = $assocArr;
			return self::WriteEscapedIniFileWithSections($path, $sections);
		}
		$content = self::AssocArraySectionToString('General', $assocArr);
		// empieza a grabar
		$handle = fopen($path, 'w');
		if ($handle === false)
			return false;
		if (fwrite($handle, $content) === false)
		{
			fclose($handle);
			return false;
		}

		fclose($handle);
		return true;
	}

	public static function RemoveExtension($filename)
	{
		$n = strrpos($filename, '.');
		if ($n === false || $n <= 0)
			return $filename;
		return substr($filename, 0, $n);
	}

	public static function EnsureExists($directory) : void
	{
		if (is_dir($directory) == false)
		{
			self::EnsureExists(dirname($directory));
			self::CreateDirectory($directory);
		}
	}

	public static function CreateDirectory($directory) : void
	{
		try
		{
			if (is_dir($directory) == false)
				mkdir($directory);
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

	public static function GetFilesRecursive($path, $ext = '', $returnFullPath = false)
	{
		return self::GetFilesStartsWithAndExt($path, '', $ext, $returnFullPath, true);
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

	public static function GetFilesStartsWithAndExt($path, $start = '', $ext = '', $returnFullPath = false, $recursive = false)
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

		if($recursive)
			$ret = self::rglob($path . '/' . $start . '*' . $ext);
		else
			$ret = glob($path . '/' . $start . '*' . $ext);

		if($notAlpha)
			$ret = preg_grep('/^' . preg_quote($path . '/', '/') . '[^a-zA-Z].*/', $ret);

		$ret = array_values(array_filter($ret, 'is_file'));

		if ($returnFullPath)
			return $ret;

		//remueve directorio base
		return preg_replace('/^' . preg_quote($path . '/', '/') . '/', '', $ret);
	}

	/**
	 * como la función glob de php pero recursiva.
	 */
	private static function rglob($pattern, $flags = 0)
	{
		$files = glob($pattern, $flags);
		foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir)
			$files = array_merge($files, self::rglob($dir . '/' . basename($pattern), $flags));

		return $files;
	}

	public static function HasFiles($path, $ext = '') : bool
	{
		if ($handle = self::OpenDirNoWarning($path))
		{
			while (false !== ($entry = readdir($handle)))
			{
				if (($ext == '' || Str::EndsWith($entry, $ext))
					&& $entry != '..' && $entry != '.' && is_file($path . '/' . $entry))
				{
					closedir($handle);
					return true;
				}
			}
			closedir($handle);
		}
		return false;
	}

	public static function GetFilesCount($path, $ext = '') : int
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

	public static function ClearDirectory($dir, bool $recursive = false) : int
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

	public static function ClearFilesOlderThan($dir, $days, $ext = '') : void
	{
		$time = time();

		$files = IO::GetFilesCursor($dir, $ext);
		while($files->GetNext())
		{
			$fileOnly = $files->Current;
			$file = $dir . "/" . $fileOnly;
			if($time - IO::FileMTime($file) >= $days * 60 * 60 * 24)
				IO::Delete($file);
		}
		$files->Close();
	}

	public static function ClearDirectoriesOlderThan($dir, $days, $ext = '') : void
	{
		$time = time();

		$directories = IO::GetDirectoriesCursor($dir, $ext);
		while($directories->GetNext())
		{
			$directoryOnly = $directories->Current;
			$directory = $dir . "/" . $directoryOnly;
			if($time - IO::FileMTime($directory . "/.") >= $days * 60 * 60 * 24)
				IO::RemoveDirectory($directory);
		}
		$directories->Close();
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
				echo $dir . '/' . $file . '<br>';
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

	public static function MoveDirectory($dirSource, $dirDest, $dirName = "", $exclusions = null, $timeFrom = null, $createEmptyFolders = true) : void
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

	public static function CopyFiles($dirSource, $dirDest, $exclusions = null, $timeFrom = null) : bool
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
				copy($dirSource . '/' . $file, $dirDest . '/' . $file);
			}
		}
		closedir($dirHandle);
		return true;
	}

	private static function doCopyDirectory($dirSource, $dirDest, $dirName, $exclusions, $timeFrom, $createEmptyFolders, $excludedExtension = '') : bool
	{
		// se fija si el source está excluido
		if ($exclusions != null)
		{
			foreach($exclusions as $exclusion)
			{
				if ($exclusion == $dirSource)
					return false;
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

						//if (file_exists($dirDest . '/' . $dirName . '/' . $file) == false
						//|| filesize($dirDest . '/' . $dirName . '/' . $file) != filesize($dirSource . '/' . $file))
						copy($dirSource . '/' . $file, $dirDest . '/' . $dirName . '/' . $file);
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
	 * Solo borra directorios vacíos.
	 */
	public static function RmDir($dir)
	{
		try
		{
			if(file_exists($dir))
				return rmdir($dir);
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
		try
		{
			Profiling::BeginTimer();
			if(System::IsWindows())
				return self::GetDirectorySizeWin($dir);

			$ret = ['size' => self::GetDirectorySizeUnix($dir)];
			if ($sizeOnly == false)
				$ret['inodes'] = self::GetDirectoryINodesCount($dir);

			return $ret;
		}
		finally
		{
			Profiling::EndTimer();
		}
	}

	private static function GetDirectorySizeWin($dir)
	{
		if(($dh = self::OpenDirNoWarning($dir)) == false)
			return ['size' => 0, 'inodes' => 0];

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

	//TODO: Sacar método de acá y usar los de la clase framework/Zip...
	public static function SendFilesToZip($zipFile, $files, $sourcefolder) : void
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
				throw new ErrorException("ERROR: Could not add file: ... <br>\n numFile:");
			if($zip->addFile(realpath($file), $path) == false)
				throw new ErrorException("ERROR: Could not add file: ... <br>\n numFile:");
		}
		// closes the archive
		$zip->close();
	}

	public static function GetTempFilename()
	{
		$path = Context::Paths()->GetTempPath();
		self::EnsureExists($path);
		$name = tempnam($path, "");
		self::Delete($name);
		return $name;
	}

	/**
	 * Creates a random unique temporary directory, with specified parameters,
	 * that does not already exist (like tempnam(), but for dirs).
	 *
	 * Created dir will begin with the specified prefix, followed by random
	 * numbers.
	 *
	 * @link https://php.net/manual/en/function.tempnam.php
	 *
	 * @param string $prefix String with which to prefix created dirs.
	 * @param int $maxAttempts Maximum attempts before giving up (to prevent
	 * endless loops).
	 *
	 * @return string|bool Full path to newly-created dir, or false on failure.
	 */
	public static function GetTempDir($prefix = 'tmp_', $maxAttempts = 1000)
	{
		$dir = Context::Paths()->GetTempPath();
		self::EnsureExists($dir);

		// Make sure characters in prefix are safe.
		if (strpbrk($prefix, '\\/:*?"<>|') !== false)
			return false;

		/* Attempt to create a random directory until it works. Abort if we reach
		 * $maxAttempts. Something screwy could be happening with the filesystem
		 * and our loop could otherwise become endless.
		 */
		$attempts = 0;
		do
		{
			$path = sprintf('%s%s%s%s', $dir, DIRECTORY_SEPARATOR, $prefix, mt_rand(100000, mt_getrandmax()));
		}
		while (mkdir($path, 0700) == false
			&& $attempts++ < $maxAttempts);

		return $path;
	}

	public static function Copy($source, $target) : bool
	{
		Profiling::BeginTimer();
		$ret = copy($source, $target);
		Profiling::EndTimer();
		return $ret;
	}

	public static function Move($source, $target) : bool
	{
		try
		{
			if(file_exists($source))
				return rename($source, $target);
		}
		catch(\Exception $e)
		{
			if($e->getCode() !== E_WARNING)
				Log::HandleSilentException($e);
		}
		return false;
	}
	public static function GetUniqueNameNoReplaceFilename($filePath) : string
	{
		$ext = self::GetFileExtension($filePath);
		$path = self::GetDirectory($filePath);
		$file = self::GetFilenameNoExtension($filePath);
		if (Str::Contains($file, ' ('))
			$file = substr($file, 0, strpos($file, '(') - 1);

		$n = 1;
		while(file_exists($ret = $path . '/' . $file . ' (' . $n . ').' . $ext))
		{
			$n++;
		}
		return $ret;
	}

	public static function IsCompressedDirectory($path) : bool
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

	public static function ReleaseCompressedDirectories() : void
	{
		if (self::$compressedDirectories != null)
		{
			foreach(self::$compressedDirectories as $folder)
				$folder->Release();
		}
	}

	public static function Delete($file) : bool
	{
		try
		{
			if (file_exists($file))
				return unlink($file);
		}
		catch(\Exception $e)
		{
			if($e->getCode() !== E_WARNING)
				Log::HandleSilentException($e);
		}
		return false;
	}

	public static function Exists($file) : bool
	{
		return file_exists($file);
	}
}
