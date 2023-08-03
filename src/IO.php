<?php

namespace minga\framework;

class IO
{
	private static array $compressedDirectories;

	public static function AppendAllBytes(string $filename, $bytes) : bool
	{
		$ret = file_put_contents($filename, $bytes, FILE_APPEND);
		if($ret === false)
			return false;
		return true;
	}

	public static function MoveDirectoryContents(string $dirSource, string $target) : void
	{
		self::EnsureExists($target);
		// limpia
		$dirname = substr($dirSource, strrpos($dirSource, "/") + 1);
		if (file_exists($target . "/" . $dirname))
			self::RemoveDirectory($target . "/" . $dirname);
		// copia
		self::CopyDirectory($dirSource, $target);
		// borra
		self::ClearDirectory($dirSource, true);
	}

	public static function ReadAllText(string $path, $maxLength = -1)
	{
		if ($maxLength == -1)
			return file_get_contents($path);
		return file_get_contents($path, false, null, 0, $maxLength);
	}

	public static function GetDirectory(string $file) : string
	{
		return pathinfo($file, PATHINFO_DIRNAME);
	}

	public static function GetDirectoryName(string $file) : string
	{
		return self::GetFilenameNoExtension(self::GetDirectory($file));
	}

	public static function GetFileExtension(string $file) : string
	{
		return pathinfo($file, PATHINFO_EXTENSION);
	}

	public static function GetFilenameNoExtension(string $file) : string
	{
		return pathinfo($file, PATHINFO_FILENAME);
	}

	public static function GetUrlNoExtension(string $file) : string
	{
		$n = strrpos($file, '.');
		if ($n !== false && $n > 0)
			return substr($file, 0, $n);

		return $file;
	}

	public static function GetRelativePath($path)
	{
		$base = Context::Paths()->GetRoot();
		if (Str::StartsWith($path, $base))
			return substr($path, strlen($base));
		return $path;
	}

	public static function ReadText(string $file, int $length) : string
	{
		$ret = file_get_contents($file, false, null, 0, $length);
		if($ret === false)
			throw new ErrorException('Error leyendo archivo');
		return $ret;
	}

	public static function ReadAllBytes(string $path) : string
	{
		$ret = file_get_contents($path);
		if($ret === false)
			throw new ErrorException('Error leyendo archivo');
		return $ret;
	}

	public static function ReadAllLines(string $path, $maxLines = null) : array
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

	public static function WriteAllText(string $path, $text) : bool
	{
		$ret = file_put_contents($path, $text);
		if($ret === false)
			return false;
		return true;
	}

	public static function WriteJson(string $path, $data, bool $pretty = false)
	{
		$flags = JSON_INVALID_UTF8_SUBSTITUTE;
		if($pretty)
			$flags |= JSON_PRETTY_PRINT;

		$json = json_encode($data, $flags);
		if($json === false)
			throw new ErrorException('Error al crear json.');
		return self::WriteAllText($path, $json);
	}

	//TODO: renombrar a StreamFile o algo así
	public static function ReadFileChunked(string $file) : bool
	{
		$handle = fopen($file, 'rb');
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
			throw new ErrorException('Error al leer json.');
		Profiling::EndTimer();
		return $ret;
	}

	public static function AppendLine(string $file, string $line) : bool
	{
		return (bool)file_put_contents($file, $line . "\r\n", FILE_APPEND);
	}

	public static function AppendLines(string $file, array $lines) : bool
	{
		return self::AppendLine($file, implode("\r\n", $lines));
	}

	public static function ReadTitleTextFile(string $file, ?string &$title, ?string &$text) : void
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

	public static function ReadKeyValueCSVFile(string $file) : array
	{
		$fp = fopen($file, 'r');
		$ret = [];
		while (($data = fgetcsv($fp)) !== false)
		{
			if (count($data) == 2)
				$ret[$data[0]] = $data[1];
		}
		fclose($fp);
		return $ret;
	}

	public static function WriteKeyValueCSVFile(string $file, array $assocArr) : void
	{
		$fp = fopen($file, 'w');
		foreach ($assocArr as $key => $value)
			fputcsv($fp, [$key, $value]);
		fclose($fp);
	}

	public static function CompareFileSize(string $fileA, string $fileB) : bool
	{
		if (file_exists($fileA) == false || file_exists($fileB) == false)
			throw new ErrorException('Archivo no encontrado para comparación de tamaños.');

		return filesize($fileA) == filesize($fileB);

	}

	public static function CompareBinaryFile(string $fileA, string $fileB) : bool
	{
		if (file_exists($fileA) == false || file_exists($fileB) == false)
			throw new ErrorException('Archivo no encontrado para comparación binaria.');

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

	public static function EscapeLongFilename(string $file)
	{
		if (Str::Contains($file, " ")
			&& Str::StartsWith($file, "'") == false
			&& Str::StartsWith($file, '"') == false)
		{
			return '"' . $file . '"';
		}
		return $file;
	}

	public static function ReadIniFile(string $file)
	{
		return parse_ini_file($file);
	}

	public static function ReadEscapedIniFile(string $file) : array
	{
		$attributes = parse_ini_file($file);
		foreach($attributes as $key => $value)
			$attributes[$key] = urldecode($value);
		return $attributes;
	}

	public static function ReadEscapedIniFileWithSections(string $file) : array
	{
		$attributes = parse_ini_file($file, true);
		foreach($attributes as &$values)
			foreach($values as $key => $value)
				$values[$key] = urldecode($value);
		return $attributes;
	}

	public static function WriteEscapedIniFileWithSections(string $file, array $assocArr) : bool
	{
		$content = "";
		foreach($assocArr as $section => $values)
			$content .= self::AssocArraySectionToString($section, $values);

		self::CreateDirectory(dirname($file));

		return self::WriteAllText($file, $content);
	}

	public static function GetSectionFromIniFile(string $path, string $section)
	{
		$sections = self::ReadEscapedIniFileWithSections($path);
		if (isset($sections[$section]))
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

	public static function WriteIniFile(string $file, array $assocArr) : bool
	{
		$content = "";
		foreach ($assocArr as $key => $elem)
			$content .= $key . '="' . $elem . "\"\r\n";

		return self::WriteAllText($file, $content);
	}

	public static function WriteEscapedIniFile(string $file, array $assocArr, bool $keepSections = false) : bool
	{
		$directory = dirname($file);

		self::CreateDirectory($directory);

		// se fija si tiene que mantener secciones
		if ($keepSections && file_exists($file))
		{
			$sections = self::ReadEscapedIniFileWithSections($file);
			$sections['General'] = $assocArr;
			return self::WriteEscapedIniFileWithSections($file, $sections);
		}
		$content = self::AssocArraySectionToString('General', $assocArr);

		return self::WriteAllText($file, $content);
	}

	public static function RemoveExtension($filename) : string
	{
		$n = strrpos($filename, '.');
		if ($n === false || $n <= 0)
			return $filename;
		return substr($filename, 0, $n);
	}

	public static function EnsureExists(string $directory) : void
	{
		if (is_dir($directory) == false)
		{
			self::EnsureExists(dirname($directory));
			self::CreateDirectory($directory);
		}
	}

	public static function CreateDirectory(string $directory) : void
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
				throw new ErrorException('No se pudo crear el directorio');
		}
	}

	public static function GetFilesCursor(string $path, string $ext = '') : FilesCursor
	{
		return new FilesCursor($path, $ext);
	}

	public static function GetDirectoriesCursor(string $path, string $ext = '') : DirectoriesCursor
	{
		return new DirectoriesCursor($path, $ext);
	}

	public static function GetFilesRecursive($path, $ext = '', bool $returnFullPath = false)
	{
		return self::GetFilesStartsWithAndExt($path, '', $ext, $returnFullPath, true);
	}

	public static function GetFilesFullPath($path, $ext = '') : array
	{
		return self::GetFiles($path, $ext, true);
	}

	public static function GetFiles($path, $ext = '', bool $returnFullPath = false) : array
	{
		return self::GetFilesStartsWithAndExt($path, '', $ext, $returnFullPath);
	}

	public static function GetFilesStartsWith($path, $start = '', bool $returnFullPath = false) : array
	{
		return self::GetFilesStartsWithAndExt($path, $start, '', $returnFullPath);
	}

	public static function GetFilesStartsWithAndExt($path, $start = '', $ext = '', bool $returnFullPath = false, bool $recursive = false) : array
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
			$ret = self::GlobR($path . '/' . $start . '*' . $ext);
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
	public static function GlobR($pattern, int $flags = 0)
	{
		$files = glob($pattern, $flags);
		foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir)
			$files = array_merge($files, self::GlobR($dir . '/' . basename($pattern), $flags));

		return $files;
	}

	public static function HasFiles(string $path, string $ext = '') : bool
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

	public static function GetDirectories($path, $start = '', bool $returnFullPath = false)
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

	public static function GetSequenceName(string $file, $index, $numLength = 5) : string
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

	public static function ClearFilesOlderThan(string $dir, int $days, string $ext = '') : void
	{
		$time = time();

		$files = self::GetFilesCursor($dir, $ext);
		while($files->GetNext())
		{
			$fileOnly = $files->Current;
			$file = $dir . "/" . $fileOnly;
			if($time - self::FileMTime($file) >= $days * 60 * 60 * 24)
				self::Delete($file);
		}
		$files->Close();
	}

	public static function ClearDirectoriesOlderThan(string $dir, int $days, string $ext = '') : void
	{
		$time = time();

		$directories = self::GetDirectoriesCursor($dir, $ext);
		while($directories->GetNext())
		{
			$directoryOnly = $directories->Current;
			$directory = $dir . "/" . $directoryOnly;
			if($time - self::FileMTime($directory . "/.") >= $days * 60 * 60 * 24)
				self::RemoveDirectory($directory);
		}
		$directories->Close();
	}

	public static function ClearFiles(string $dir, string $extension, bool $recursive = false, bool $showOnly = false) : int
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

	public static function MoveDirectory(string $dirSource, string $dirDest, $dirName = "", ?array $exclusions = null, $timeFrom = null, bool $createEmptyFolders = true) : void
	{
		self::CopyDirectory($dirSource, $dirDest, $dirName, $exclusions, $timeFrom, $createEmptyFolders);
		self::RemoveDirectory($dirSource);
	}

	//TODO: no hace falta nullable en $exclusions
	public static function CopyDirectory(string $dirSource, string $dirDest, $dirName = "", ?array $exclusions = null, $timeFrom = null, bool $createEmptyFolders = true, $excludedExtension = '') : bool
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

	public static function CopyFiles(string $dirSource, string $dirDest, ?array $exclusions = null, $timeFrom = null) : bool
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

	private static function doCopyDirectory(string $dirSource, string $dirDest, $dirName, ?array $exclusions, $timeFrom, bool $createEmptyFolders, $excludedExtension = '') : bool
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
	public static function RemoveDirectory(string $dir) : int
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
	public static function RmDir(string $dir) : bool
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

	public static function OpenDirNoWarning(string $dir)
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

	public static function GetDirectorySize($dir, bool $sizeOnly = false)
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

	private static function GetDirectorySizeWin(string $dir) : array
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

	public static function GetTempFilename() : string
	{
		$path = Context::Paths()->GetTempPath();
		self::EnsureExists($path);
		$name = tempnam($path, "");
		if($name === false)
			throw new ErrorException('GetTempFilename falló');
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
	 * @return string Full path to newly-created dir, or trhows on failure.
	 */
	public static function GetTempDir(string $prefix = 'tmp_', int $maxAttempts = 1000) : string
	{
		$dir = Context::Paths()->GetTempPath();
		self::EnsureExists($dir);

		// Make sure characters in prefix are safe.
		if (strpbrk($prefix, '\\/:*?"<>|') !== false)
			throw new ErrorException('GetTempDir falló');

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

	public static function Copy(string $source, string $target) : bool
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

	public static function GetUniqueNameNoReplaceFilename(string $filePath) : string
	{
		$ext = self::GetFileExtension($filePath);
		$path = self::GetDirectory($filePath);
		$file = self::GetFilenameNoExtension($filePath);
		if (Str::Contains($file, ' ('))
			$file = substr($file, 0, strpos($file, '(') - 1);

		$n = 1;
		while(file_exists($ret = $path . '/' . $file . ' (' . $n . ').' . $ext))
			$n++;
		return $ret;
	}

	public static function IsCompressedDirectory(string $path) : bool
	{
		$dir = new CompressedDirectory($path);
		$simple = $dir->IsCompressed();
		if ($simple)
			return true;
		$dir = new CompressedInParentDirectory($path);
		return $dir->IsCompressed();
	}

	public static function GetCompressedDirectory(string $path)
	{
		if (isset(self::$compressedDirectories))
		{
			foreach(self::$compressedDirectories as $compressedDir)
			{
				if ($compressedDir->path == $path)
					return $compressedDir;
			}
		}
		$dir = new CompressedDirectory($path);
		if ($dir->IsCompressed() == false)
			$dir = new CompressedInParentDirectory($path);

		if (isset(self::$compressedDirectories) == false)
			self::$compressedDirectories = [];
		self::$compressedDirectories[] = $dir;
		return $dir;
	}

	public static function ReleaseCompressedDirectories() : void
	{
		if (isset(self::$compressedDirectories))
		{
			foreach(self::$compressedDirectories as $compressedDir)
				$compressedDir->Release();
		}
	}

	public static function Delete(string $file) : bool
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

	public static function Exists(string $file) : bool
	{
		return file_exists($file);
	}
}
