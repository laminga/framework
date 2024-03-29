<?php

namespace minga\framework;

use minga\framework\locking\BackupLock;

class Backup
{
	public const MAX_ZIP_SIZE = 5000000;
	private static array $blocks = ["file_modified", "file_deleted", "directory_created", "directory_deleted"];

	public static array $exclusions = [
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
		"/stats",
	];

	public static function AppendModified(string $file) : void
	{
		self::AppendEntry("file_modified", $file);
	}

	public static function AppendDeleted(string $file) : void
	{
		self::AppendEntry("file_deleted", $file);
	}

	public static function AppendDirectoryCreated(string $file) : void
	{
		self::AppendEntry("directory_created", $file);
	}

	public static function AppendDirectoryDeleted(string $file) : void
	{
		self::AppendEntry("directory_deleted", $file);
	}

	public static function AppendEntry(string $set, string $file) : void
	{
		if (self::GetState() == "")
			return;
		$relative = self::GetRelativePath($file);
		if ($relative != "" && self::IsInExcludedFolder($relative) == false)
		{
			$backupFolder = Context::Paths()->GetBackupLocalPath();
			$lock = new BackupLock($set);
			$filedQ = FiledQueue::Create($lock, $backupFolder, $set . ".txt");
			$lock->LockWrite();
			$filedQ->Append($relative);
			$lock->Release();
		}
	}

	private static function GetRelativePath(string $file) : string
	{
		$storage = Context::Paths()->GetStorageRoot();
		if (Str::StartsWith($file, $storage))
			return substr($file, strlen($storage));
		return "";
	}

	private static function IsInExcludedFolder(string $file) : bool
	{
		$file = Str::Replace($file, "\\", "/");
		foreach(self::$exclusions as $exc)
		{
			if (Str::StartsWith($file, $exc . "/"))
				return true;
		}
		return false;
	}

	public function CreateCheckpoint() : void
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

	private function InitializeFolder() : void
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

	public function CreateLocalCopyProfiles() : void
	{
		throw new \Exception('No implementado.');
	}

	public function CreateLocalCopyEvents() : void
	{
		throw new \Exception('No implementado.');
	}

	public function CreateLocalCopySite() : void
	{
		$backupFolder = Context::Paths()->GetBackupLocalPath();
		$backupFolderWorkingFolder = $backupFolder . "/backup";
		self::CheckState("CHECKPOINTCREATED");
		self::Log('Incio de copia de seguridad del sitio');

		$this->InitializeFolder();
		// Copia todo lo modificado hasta la actualidad

		// 1. Deja archivos con entradas únicas.
		//	private static $blocks = ["file_modified", "file_deleted", "directory_created", "directory_deleted"];

		// 2. Valida y consolida creado y borrado
		//		private static $blocks = ["file_modified", "file_deleted", "directory_created", "directory_deleted"];

		// 3. Hace copia física de creado
		self::Log('Inicia copia', true);

		self::Log('Copia lista de eliminados', true);
		// 4. borrados
		if (file_exists($backupFolder . "/deleted_checkpoint.txt"))
			copy($backupFolder . "/deleted_checkpoint.txt", $backupFolderWorkingFolder . "/deleted_checkpoint.txt");
		// guarda el estado
		self::SaveState("COPYSITEDONE");

	}

	public function SplitLargeFiles() : void
	{
		self::CheckState("COPYEVENTSDONE");
		self::Log('Comenzando división', true);

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
		self::Log('Fin división', true);

	}

	private static function ScanAndSplitRecursive(string $dirSource, int $maxSize) : void
	{
		$dirHandle = null;
		if(is_dir($dirSource))
			$dirHandle = IO::OpenDirNoWarning($dirSource);
		if($dirHandle === false)
			return;
		while($file = readdir($dirHandle))
		{
			if($file != '.' && $file != '..')
			{
				$filename = $dirSource . '/' . $file;
				if(!is_dir($filename))
				{
					if (filesize($filename) > $maxSize)
						Multipart::Split($filename, $maxSize);
				}
				else
					self::ScanAndSplitRecursive($dirSource . '/' . $file, $maxSize);
			}
		}
		if($dirHandle != null)
			closedir($dirHandle);
	}

	public function CreateZipChunk() : void
	{
		self::CheckState("ZIPPEDPARTIALLY", "SPLITDONE");

		$backupFolder = Context::Paths()->GetBackupLocalPath();
		$filename = $backupFolder . "/backup.zip";
		IO::Delete($filename);
		$zip = new Zip($filename);
		$zip->lock->LockWrite();

		// Zipea todo lo modificado hasta la actualidad
		$backupFolderWorkingFolder = $backupFolder . "/backup";

		self::Log('Inicia zip', true);
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
		self::Log('Fin copia de seguridad', true);
	}

	public static function SaveState(string $state) : void
	{
		$backupFolder = Context::Paths()->GetBackupLocalPath();
		$ret = $backupFolder . "/state.txt";
		IO::WriteAllText($ret, $state);
	}

	public static function GetState() : string
	{
		$backupFolder = Context::Paths()->GetBackupLocalPath();
		$ret = $backupFolder . "/state.txt";
		if (file_exists($ret) == false)
			return "";

		return IO::ReadAllText($ret);
	}

	public static function CheckState(string $state1, $state2 = "omited") : void
	{
		$text = self::GetState();
		if ($text == $state1 || $text == $state2)
			return;
		throw new ErrorException('El estado de copia de seguridad no es válido para el pedido.');
	}

	public static function Log(string $text, bool $append = false) : void
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

	public static function ReadCheckpoint() : int
	{
		return IO::FileMTime(self::BeginCheckPointFile());
	}

	private static function BeginCheckPointFile() : string
	{
		$backupFolder = Context::Paths()->GetBackupLocalPath();
		$ret = $backupFolder . "/checkpointbegin";
		if (file_exists($ret) == false)
			IO::WriteAllText($ret, "");
		return $ret;
	}

	private static function EndCheckPointFile() : string
	{
		$backupFolder = Context::Paths()->GetBackupLocalPath();
		$ret = $backupFolder . "/checkpointend";
		if (file_exists($ret) == false)
			IO::WriteAllText($ret, "");
		return $ret;
	}

	public function GetFiles() : void
	{
		self::CheckState("ZIPPED", "ZIPPEDPARTIALLY");

		$backupFolder = Context::Paths()->GetBackupLocalPath();
		$zip = $backupFolder . "/backup.zip";
		if (file_exists($zip) == false)
			self::SaveState("CREATED");

		self::SendFile($zip, "application/zip");
	}

	private static function SendFile(string $filename, string $contentType) : void
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

	public function NotifyGetFilesCompleted() : void
	{
		self::CheckState("ZIPPED", "DOWNLOADED");

		self::SaveState("DOWNLOADED");
	}
}
