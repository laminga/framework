<?php

namespace minga\framework;

use minga\framework\locking\BackupLock;

class Backup
{
	public const MAX_ZIP_SIZE = 5000000;
	private static $blocks = ["file_modified", "file_deleted", "directory_created", "directory_deleted"];

	public static $exclusions = [
		"/caches",
		"/temp",
		"/dump",
		"/log",
		"/sitemap",
		"/traffic",
		"/yearly",
		"/performance",
		"/buckets",
		"/backup",
		"/tokens",
		"/stats"
	];

	public static function AppendModified($file)
	{
		self::AppendEntry("file_modified", $file);
	}

	public static function AppendDeleted($file)
	{
		self::AppendEntry("file_deleted", $file);
	}

	public static function AppendDirectoryCreated($file)
	{
		self::AppendEntry("directory_created", $file);
	}

	public static function AppendDirectoryDeleted($file)
	{
		self::AppendEntry("directory_deleted", $file);
	}

	public static function AppendEntry($set, $file)
	{
		if (self::GetState() == "")
			return;
		$relative = self::GetRelativePath($file);
		if ($relative !== null && self::IsInExcludedFolder($relative) == false)
		{
			$backupFolder = Context::Paths()->GetBackupLocalPath();
			$lock = new BackupLock($set);
			$file = FiledQueue::Create($lock, $backupFolder, $set . ".txt");
			$lock->LockWrite();
			$file->Append($relative);
			$lock->Release();
		}
	}

	private static function GetRelativePath($file)
	{
		$storage = Context::Paths()->GetStorageRoot();
		if (Str::StartsWith($file, $storage))
		{
			return substr($file, strlen($storage));
		}
		return null;
	}

	private static function IsInExcludedFolder($file)
	{
		$file = Str::Replace($file, "\\", "/");
		foreach(self::$exclusions as $exc)
		{
			if (Str::StartsWith($file, $exc . "/"))
			{
				return true;
			}
		}
		return false;
	}

	public function CreateCheckpoint()
	{
		self::CheckState("DOWNLOADED");

		// Mueve el deleted
		// 1. prepara
		$backupFolder = Context::Paths()->GetBackupLocalPath();
		IO::Delete($backupFolder . "/deleted_checkpoint.txt");
		// 2. Guarda hora en memoria
		$now = time();

		// 3. Mueve
		foreach(self::$blocks as $block)
		{
			if (file_exists($backupFolder . "/" . $block . ".txt") == false)
				IO::AppendLine($backupFolder . "/" . $block . ".txt", "");
			IO::Move($backupFolder . "/" . $block . ".txt", $backupFolder . "/" . $block . "_checkpoint.txt");
		}
		// Guarda la hora en disco tomando el último fin como inicio
		$beginCheckPointFile = self::BeginCheckPointFile();
		$endCheckPointFile = self::EndCheckPointFile();
		$ending = IO::FileMTime($endCheckPointFile);
		touch($beginCheckPointFile, $ending);
		touch($endCheckPointFile, $now);

		// guarda el estado
		self::SaveState("CHECKPOINTCREATED");
	}

	private function initializeFolder()
	{
		$backupFolder = Context::Paths()->GetBackupLocalPath();
		$backupFolderWorkingFolder = $backupFolder . "/backup";
	  	$filename = $backupFolder . "/backup.zip";
		IO::Delete($filename);
		// Copia todo lo modificado hasta la actualidad
		IO::RemoveDirectory($backupFolderWorkingFolder . '/root');
		IO::EnsureExists($backupFolderWorkingFolder . '/root');
		IO::RemoveDirectory($backupFolderWorkingFolder . '/storage');
		IO::EnsureExists($backupFolderWorkingFolder . '/storage');
	}

	public function CreateLocalCopyProfiles()
	{
		throw new \Exception('No implementado.');
	}

	public function CreateLocalCopyEvents()
	{
		throw new \Exception('No implementado.');
	}

	public function CreateLocalCopySite()
	{
		$backupFolder = Context::Paths()->GetBackupLocalPath();
		$backupFolderWorkingFolder = $backupFolder . "/backup";
		self::CheckState("CHECKPOINTCREATED");
		self::Log("Begin site backup");
		
		$this->initializeFolder();
		// Copia todo lo modificado hasta la actualidad

		// 1. Deja archivos con entradas únicas.
		//	private static $blocks = ["file_modified", "file_deleted", "directory_created", "directory_deleted"];

		// 2. Valida y consolida creado y borrado
		//		private static $blocks = ["file_modified", "file_deleted", "directory_created", "directory_deleted"];

		// 3. Hace copia física de creado
		self::Log("Begin copy", true);

		self::Log("Copy deleted list", true);
		// 4. borrados
		if (file_exists($backupFolder . "/deleted_checkpoint.txt"))
			copy($backupFolder . "/deleted_checkpoint.txt", $backupFolderWorkingFolder . "/deleted_checkpoint.txt");
		// guarda el estado
		self::SaveState("COPYSITEDONE");
		return;
	}

	public function SplitLargeFiles()
	{
		self::CheckState("COPYEVENTSDONE");
		self::Log("Begin splitting", true);

		$backupFolder = Context::Paths()->GetBackupLocalPath();
		$filename = $backupFolder . "/backup.zip";
		$zip = new Zip($filename);
		$zip->lock->LockWrite();
		// Se fija si hay archivo que tienen que viajar en múltiples volúmenes
		$backupFolderWorkingFolder = $backupFolder . "/backup";
		$maxSize = self::MAX_ZIP_SIZE;
		self::ScanAndSplitRecursive($backupFolderWorkingFolder, $maxSize);
		// guarda el estado
		self::SaveState("SPLITDONE");
		$zip->lock->Release();
		self::Log("End splitting", true);
		return;
	}

	private static function ScanAndSplitRecursive($dirsource, $maxSize)
	{
		$dir_handle = null;
		if(is_dir($dirsource))
			$dir_handle = IO::OpenDirNoWarning($dirsource);
		if($dir_handle === false)
			return;
		while($file = readdir($dir_handle))
		{
			if($file != '.' && $file != '..')
			{
				$filename = $dirsource . '/' . $file;
				if(!is_dir($filename))
				{
					if (filesize($filename) > $maxSize)
						Multipart::Split($filename , $maxSize);
				}
				else
					self::ScanAndSplitRecursive($dirsource . '/' . $file, $maxSize);
			}
		}
		if($dir_handle != null)
			closedir($dir_handle);
	}

	public function CreateZipChunk()
	{
		self::CheckState("ZIPPEDPARTIALLY", "SPLITDONE");

		$backupFolder = Context::Paths()->GetBackupLocalPath();
		$filename = $backupFolder . "/backup.zip";
		IO::Delete($filename);
		$zip = new Zip($filename);
		$zip->lock->LockWrite();

		// Zipea todo lo modificado hasta la actualidad
		$backupFolderWorkingFolder = $backupFolder . "/backup";

		self::Log("Begin zip", true);
		// Comprime
		$current = 0;
		$zipedAll = $zip->AppendFilesToZipRecursiveDeleting($backupFolderWorkingFolder, [''], '', self::MAX_ZIP_SIZE, $current);

		if ($zipedAll)
		{
			// Limpia
			IO::RemoveDirectory($backupFolderWorkingFolder);
			// guarda el estado
			self::SaveState("ZIPPED");
		}
		else
		{
			// guarda el estado
			self::SaveState("ZIPPEDPARTIALLY");
		}
		$zip->lock->Release();
		self::Log("End backup", true);
	}

	public static function SaveState($state)
	{
		$backupFolder = Context::Paths()->GetBackupLocalPath();
		$ret = $backupFolder . "/state.txt";
		IO::WriteAllText($ret, $state);
	}

	public static function GetState()
	{
		$backupFolder = Context::Paths()->GetBackupLocalPath();
		$ret = $backupFolder . "/state.txt";
		if (file_exists($ret) == false)
			return "";
		else
			return IO::ReadAllText($ret);
	}

	public static function CheckState($state1, $state2 = "ommited")
	{
		$text = self::GetState();
		if ($text == $state1 || $text == $state2)
			return;
		throw new \Exception('Invalid backup state for request.');
	}

	public static function Log($text, $append = false)
	{
		$backupFolder = Context::Paths()->GetBackupLocalPath();
		$ret = $backupFolder . "/log.txt";
		$text .= " at " . date("m-d-Y H:i:s");
		$ms = round(microtime(true) * 1000);
		$text .= "." . ($ms % 1000);
		if ($append == false)
			IO::WriteAllText($ret, $text . "\r\n");
		else
			IO::AppendLine($ret, $text);
	}

	public static function ReadCheckpoint()
	{
		return IO::FileMTime(self::BeginCheckPointFile());
	}

	private static function BeginCheckPointFile()
	{
		$backupFolder = Context::Paths()->GetBackupLocalPath();
		$ret = $backupFolder . "/checkpointbegin";
		if (file_exists($ret) == false)
			IO::WriteAllText($ret, "");
		return $ret;
	}

	private static function EndCheckPointFile()
	{
		$backupFolder = Context::Paths()->GetBackupLocalPath();
		$ret = $backupFolder . "/checkpointend";
		if (file_exists($ret) == false)
			IO::WriteAllText($ret, "");
		return $ret;
	}

	public function GetFiles()
	{
		self::CheckState("ZIPPED", "ZIPPEDPARTIALLY");

		$backupFolder = Context::Paths()->GetBackupLocalPath();
		$zip = $backupFolder . "/backup.zip";
		if (file_exists($zip) == false)
			self::SaveState("CREATED");

		self::SendFile($zip, "application/zip");
	}

	private static function SendFile($filename, $contentType)
	{
		$size = Zipping::Filesize($filename);

		// send the right headers
		header('Content-Type: ' . $contentType);
		header('Content-Length: ' . $size);
		header('Content-Disposition: filename="' . stripslashes(basename($filename)) . '"');

		if(ob_get_length())
			ob_clean();
		flush();

		if ($size < 15 * 1024 * 1024)
			ob_start();
		else if (ob_get_length())
			ob_end_flush();

		readfile($filename);
		exit;
	}

	public function NotifyGetFilesCompleted()
	{
		self::CheckState("ZIPPED", "DOWNLOADED");

		self::SaveState("DOWNLOADED");
	}
}
