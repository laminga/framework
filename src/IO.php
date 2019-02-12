<?php

namespace minga\framework;

class IO
{
	private static $compressedDirectories;

		public static function AppendAllBytes($filename, $bytes)
	{
		$fp = fopen($filename, 'a');
		fwrite($fp, $bytes);
		fclose($fp);
	}
	public static function Execute($command, array $args = array(), array &$lines = array(), $redirectStdErr = true)
	{
		$stdErr = '';
		if($redirectStdErr)
			$stdErr = ' 2>&1';
		$str = '';
		foreach($args as $arg)
			$str .= escapeshellarg($arg).' ';
		$val = 0;
		exec($command.' '.trim($str).$stdErr, $lines, $val);
		return $val;
	}


	public static function MoveDirectoryContents($dirsource, $target)
	{
		IO::EnsureExists($target);
		// limpia
		$dirname = substr($dirsource,strrpos($dirsource,"/")+1);
		if (file_exists($target."/".$dirname))
			IO::RemoveDirectory($target."/".$dirname);
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
		$handle = fopen($path, "r");
		$contents = fread($handle, $length);
		fclose($handle);
		return $contents;
	}

	public static function ReadAllLines($path)
	{
		$handle = fopen($path, 'r');
		$ret = array();
		while (!feof($handle))
		{
			$currentLine = fgets($handle) ;
			$ret[] = $currentLine;
		}
		fclose($handle);
		return $ret;
	}

	public static function WriteAllText($path, $text)
	{
		return file_put_contents($path, $text);
	}

	public static function WriteJson($path, $text, $pretty = false)
	{
		$flags = 0;
		if($pretty)
			$flags = JSON_PRETTY_PRINT;
		return self::WriteAllText($path, json_encode($text, $flags));
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

	public static function AppendLine($path, $line)
	{
		if (!$handle = fopen($path, 'a'))
			return false;
		if (!fwrite($handle, $line . "\r\n"))
			return false;
		fclose($handle);
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
		$fp = fopen($path, 'r');
		$ret = array();
		while (($data = fgetcsv($fp)) !== false)
		{
			if (sizeof($data) == 2)
				$ret[$data[0]] = $data[1];
		}
		fclose($fp);
		return $ret;
	}

	public static function WriteKeyValueCSVFile($path, $assoc_arr)
	{
		$fp = fopen($path, 'w');
		foreach ($assoc_arr as $key => $value)
			fputcsv($fp, array($key, $value));
		fclose($fp);
	}

	public static function CompareFileSize($file_a, $file_b)
	{
		if (file_exists($file_a) == false || file_exists($file_b) == false)
			MessageBox::ThrowMessage("Archivo no encontrado para comparación binaria.");

		return (filesize($file_a) == filesize($file_b));

	}

	public static function CompareBinaryFile($file_a, $file_b)
	{
		if (file_exists($file_a) == false || file_exists($file_b) == false)
			MessageBox::ThrowMessage("Archivo no encontrado para comparación binaria.");

		if (filesize($file_a) == filesize($file_b))
		{
			$fp_a = fopen($file_a, 'rb');
			$fp_b = fopen($file_b, 'rb');

			while (($b = fread($fp_a, 4096)) !== false)
			{
				$b_b = fread($fp_b, 4096);
				if ($b !== $b_b)
				{
					fclose($fp_a);
					fclose($fp_b);
					return false;
				}
			}

			fclose($fp_a);
			fclose($fp_b);

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

	public static function WriteEscapedIniFileWithSections($path, $assoc_arr)
	{
		$content = "";
		foreach($assoc_arr as $section => $values)
			$content .= self::AssocArraySectionToString($section, $values);

		$directory = dirname($path);
		if (!file_exists($directory))
			mkdir($directory);

		if (!$handle = fopen($path, 'w'))
			return false;
		if (!fwrite($handle, $content))
			return false;

		fclose($handle);
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

	public static function WriteIniFile($path, $assoc_arr)
	{
		if (!$handle = fopen($path, 'w'))
			return false;
		$content = "";
		foreach ($assoc_arr as $key => $elem)
		{
			$content .= $key."=\"".$elem."\"\r\n";
		}
		fwrite($handle, $content);
		fclose($handle);
		return true;
	}

	public static function WriteEscapedIniFile($path, $assoc_arr, $keepSections = false)
	{
		$directory = dirname($path);
		if (!file_exists($directory))
			mkdir($directory);

		// se fija si tiene que mantener secciones
		if ($keepSections && file_exists($path))
		{
			$sections = self::ReadEscapedIniFileWithSections($path);
			$sections['General'] = $assoc_arr;
			return self::WriteEscapedIniFileWithSections($path, $sections);
		}
		$content = self::AssocArraySectionToString('General', $assoc_arr);
		// empieza a grabar
		if (!$handle = fopen($path, 'w'))
			return false;
		if (!fwrite($handle, $content))
			return false;

		fclose($handle);
		return true;
	}

	public static function RemoveExtension($filename)
	{
		$n = strrpos($filename, '.');
		if ($n <= 0)
			return $filename;
		$file = substr($filename, 0, $n);
		return $file;
	}

	public static function EnsureExists($directory)
	{
		if (!is_dir($directory))
		{
			self::EnsureExists(dirname($directory));
			self::CreateDirectory($directory);
		}
	}

	public static function CreateDirectory($directory)
	{
		try
		{
			mkdir($directory);
		}
		catch (ErrorException $e)
		{
			/* Este catch está porque incluso chequeando con if exists antes,
				pueda haber concurrencia entre if exists y mkdir, y en consecuencia
				sale el mkdir con error de 'directorio ya existe'. Salir con ese
				error no es útil, dado que el objetivo de este método es crear
				el directorio. Se podría generar un lock a nivel aplicación para
				hacer un if exist con lock, pero el beneficio es poco claro.
			 */
			if (!is_dir($directory))
				throw $e;
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
		if (!file_exists($dir))
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
		if (!file_exists($dir))
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
		catch(ErrorException $e)
		{
			if($e->getCode() !== E_WARNING)
				Log::HandleSilentException($e);
		}
		return false;
	}

	public static function MoveDirectory($dirsource, $dirdest, $dirname = "", $exclusions = null, $timeFrom = null, $createEmptyFolders = true)
	{
		self::CopyDirectory($dirsource, $dirdest, $dirname, $exclusions, $timeFrom, $createEmptyFolders);
		self::RemoveDirectory($dirsource);
	}

	public static function CopyDirectory($dirsource, $dirdest, $dirname = "", $exclusions = null, $timeFrom = null, $createEmptyFolders = true, $excludedExtension = '')
	{
		// se fija si el source está excluido
		if ($exclusions != null)
		{
			$exclusionsFull = array();
			foreach($exclusions as $exclusion)
			{
				$exclusionsFull[] = $dirsource . "/" . $exclusion;
			}
			$exclusions = $exclusionsFull;
		}
		return self::doCopyDirectory($dirsource, $dirdest, $dirname, $exclusions, $timeFrom, $createEmptyFolders, $excludedExtension);
	}

	public static function CopyFiles($dirsource, $dirdest, $exclusions = null, $timeFrom = null)
	{
		// se fija si el source está excluido
		if ($exclusions != null)
		{
			$exclusionsFull = array();
			foreach($exclusions as $exclusion)
			{
				$exclusionsFull[] = $dirsource . "/" . $exclusion;
			}
			$exclusions = $exclusionsFull;
		}
		$dir_handle = self::OpenDirNoWarning($dirsource);

		while($file = readdir($dir_handle))
		{
			if($file != '.' && $file != '..')
			{
				if(!is_dir($dirsource . '/' . $file))
				{
					if ($timeFrom == null || self::FileMTime($dirsource . '/' . $file) >= $timeFrom)
					{
						copy ($dirsource.'/'.$file, $dirdest.'/'.$file);
					}
				}
			}
		}
		closedir($dir_handle);
		return true;
	}

	private static function doCopyDirectory($dirsource, $dirdest, $dirname, $exclusions, $timeFrom, $createEmptyFolders, $excludedExtension ='')
	{
		// se fija si el source está excluido
		if ($exclusions != null)
		{
			foreach($exclusions as $exclusion)
			{
				if ($exclusion == $dirsource)
					return;
			}
		}

		// recursive function to copy all subdirectories and contents
		$dir_handle = null;
		if(is_dir($dirsource))
			$dir_handle = self::OpenDirNoWarning($dirsource);
		if ($dirname == '')
			$dirname = substr($dirsource, strrpos($dirsource, '/') + 1);

		if ($createEmptyFolders)
		{
			self::EnsureExists($dirdest);
			mkdir($dirdest.'/'.$dirname, 0750);
		}

		while($file = readdir($dir_handle))
		{
			if($file != '.' && $file != '..')
			{
				if(!is_dir($dirsource . '/' . $file))
				{
					if ($timeFrom == null || self::FileMTime($dirsource.'/'.$file) >= $timeFrom)
					{
						if ($excludedExtension == '' || Str::EndsWith($file, '.' . $excludedExtension) == false)
						{
							if ($createEmptyFolders == false)
								self::EnsureExists($dirdest.'/'.$dirname);

							//if (file_exists($dirdest.'/'.$dirname.'/'.$file) == false || filesize($dirdest.'/'.$dirname.'/'.$file) != filesize($dirsource.'/'.$file))
							copy ($dirsource.'/'.$file, $dirdest.'/'.$dirname.'/'.$file);
						}
					}
				}
				else
				{
					$dirdest1 = $dirdest.'/'.$dirname;
					self::doCopyDirectory($dirsource.'/'.$file, $dirdest1, '', $exclusions, $timeFrom, $createEmptyFolders, $excludedExtension);
				}
			}
		}
		closedir($dir_handle);
		return true;
	}

	/**
	 * Remueve directorio completo aunque
	 * contenga archivos.
	 */
	public static function RemoveDirectory($dir)
	{
		if (!file_exists($dir))
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
				$n += self::RemoveDirectory($dir.'/'.$file);
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
				return rmdir($dir);
		}
		catch(ErrorException $e)
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
		catch(ErrorException $e)
		{
			if($e->getCode() !== E_WARNING)
				Log::HandleSilentException($e);
		}
		return false;
	}

	public static function GetDirectoryINodesCount($dir)
	{
		Profiling::BeginTimer();
		$ret = exec("find " . $dir . "/. | wc -l");
		Profiling::EndTimer();
		return $ret;
	}

	public static function GetDirectorySizeUnix($dir)
	{
		Profiling::BeginTimer();
		$ret = exec("/usr/bin/du -sb " . $dir);
		$pos = strpos($ret, "\t");
		if($pos === false)
			return 0;
		$size = substr($ret, 0, $pos);
		Profiling::EndTimer();
		return $size;
	}

	public static function GetDirectorySize($dir, $sizeOnly = false)
	{
		Profiling::BeginTimer();
		if(System::IsOnIIS())
			$ret = self::GetDirectorySizeWin($dir);
		else
		{
			$ret = array('size' => self::GetDirectorySizeUnix($dir));
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
			return array('size' => 0, 'inodes' => 0);
		}

		$size = 0;
		$n = 0;
		$inodes = 1;
		while(($file = readdir($dh)) !== false)
		{
			if($file !== '.' && $file !== '..')
			{
				$item = $dir.'/'.$file;
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

		return array('size' => $size, 'inodes' => $inodes);
	}

	public static function SendFilesToZip($zipFile, $files, $sourcefolder)
	{
		self::Delete($zipFile);
		$zip = new \ZipArchive();
		if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true)
			throw new ErrorException("Could not open archive");

		// adds files to the file list
		$sourcefolder = str_replace("\\", "/", $sourcefolder);
		if (!Str::EndsWith($sourcefolder, "/"))
			$sourcefolder .= "/";
		foreach ($files as $file)
		{
			//fix archive paths
			$fileFixed = str_replace("\\", "/", $file);
			$path = str_replace($sourcefolder, "", $fileFixed); //remove the source path from the $key to return only the file-folder structure from the root of the source folder

			if (!file_exists($file))
				throw new ErrorException('file does not exist. Please contact your administrator or try again later.');
			if (!is_readable($file))
				throw new ErrorException('file not readable. Please contact your administrator or try again later.');

			if($zip->addFromString($path, $file) == false)
				throw new ErrorException("ERROR: Could not add file: ... </br> numFile:");
			if($zip->addFile(realpath($file), $path) == false)
				throw new ErrorException("ERROR: Could not add file: ... </br> numFile:");
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

	public static function Copy($source, $target)
	{
		Profiling::BeginTimer();

		copy($source, $target);

		Profiling::EndTimer();
	}

	public static function Move($source, $target)
	{
		try
		{
			if(file_exists($source))
				return rename($source, $target);
		}
		catch(ErrorException $e)
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
			foreach(self::$compressedDirectories as $folder)
				if ($folder->path == $path)
					return $folder;
		$dir = new CompressedDirectory($path);
		if ($dir->IsCompressed() == false)
			$dir = new CompressedInParentDirectory($path);

		if (self::$compressedDirectories == null)
			self::$compressedDirectories = array();
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
				return unlink($file);
		}
		catch(ErrorException $e)
		{
			if($e->getCode() !== E_WARNING)
				Log::HandleSilentException($e);
		}
		return false;
	}
	public static function Exists($file)
	{
		return (file_exists($file));
	}
}
