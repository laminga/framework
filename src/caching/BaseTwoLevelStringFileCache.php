<?php

namespace minga\framework\caching;

use minga\framework\Context;
use minga\framework\IO;
use minga\framework\Profiling;
use minga\framework\settings\CacheSettings;

class BaseTwoLevelStringFileCache
{
	private string $path;
	private int $LimitMB;

	public function __construct(string $path, bool $isAbsolutePath = false, int $limitMB = -1)
	{
		$this->LimitMB = $limitMB;
		if ($isAbsolutePath)
			$this->path = $path;
		else
			$this->path = Context::Paths()->GetStorageCaches() . '/services/' . $path;
		IO::EnsureExists($this->path);
	}

	public function Clear($key1 = null, $key2 = null) : void
	{
		if ($key1 == null)
		{
			IO::ClearDirectory($this->path, true);
			return;
		}
		$key1 = (string)$key1;
		$key2 = (string)$key2;

		$folder = $this->path . "/" . $key1;
		if ($key2 === '' && file_exists($folder))
		{
			if (is_dir($folder))
				IO::RemoveDirectory($folder);
			else
				IO::Delete($folder);
			return;
		}
		$file = $this->ResolveFilename($key1, $key2, false);
		IO::Delete($file);
		$fileRaw = $this->ResolveFilenameRaw($key1, $key2, false);
		IO::Delete($fileRaw);
	}

	public function HasData($key1, $key2, &$value = null) : bool
	{
		if (Context::Settings()->Cache()->Enabled !== CacheSettings::Enabled)
			return false;

		$file = $this->ResolveFilename($key1, $key2);
		if (file_exists($file))
		{
			Profiling::BeginTimer();
			$value = IO::ReadAllText($file);
			touch($file);
			Profiling::EndTimer();
			return true;
		}

		return false;
	}

	public function HasRawData($key1, $key2, &$value = null) : bool
	{
		if (Context::Settings()->Cache()->Enabled !== CacheSettings::Enabled)
			return false;

		$file = $this->ResolveFilenameRaw($key1, $key2);
		if (file_exists($file))
		{
			Profiling::BeginTimer();
			$value = IO::ReadAllText($file);
			touch($file);
			Profiling::EndTimer();
			return true;
		}
		return false;
	}
	public function DataSizeMB($key1): int
	{
		return $this->DiskSizeMB($key1);
	}
	public function DiskSizeMB($key1): int
	{
		if ($key1 == '')
			$folder = $this->ResolveFolder($key1, '', false);
		else
			$folder = $this->ResolveFolder($key1, '@', false);
		$info = IO::GetDirectorySize($folder, false);
		return $info['size'] / 1024 / 1024;
	}

	private function CheckLimits($key1, $key2): void
	{
		if ($this->LimitMB === -1)
			return;
		// Le toca?
		if (mt_rand(1, 50) !== 1)
			return;
		// Puede leer?
		$folder = $this->ResolveFolder($key1, $key2, false);
		if (!is_dir($folder))
			return;
		// Excedió la cuota?
		$info = IO::GetDirectorySize($folder, false);
		if ($info['size'] / 1024 / 1024 > $this->LimitMB)
		{
			$this->FreeQuota($folder, 10);
		}
	}

	public function FreeQuota(string $folder, int $percentage): int
	{
		if (!is_dir($folder)) {
			return 0;
		}

		// Obtener archivos con sus tiempos de acceso (en linux es casi siempre la fecha de creación)
		$files = [];
		foreach (scandir($folder) as $filename) {
			$path = $folder . '/' . $filename;
			if (is_file($path)) {
				$files[] = [
					'path' => $path,
					'atime' => fileatime($path)
				];
			}
		}

		$total = count($files);
		if ($total === 0) {
			return 0;
		}

		// Calcular cuántos eliminar
		$toDelete = (int) ($total * $percentage / 100);
		if ($toDelete === 0) {
			return 0;
		}

		// Ordenar por acceso (más viejos primero)
		usort($files, fn($a, $b) => $a['atime'] <=> $b['atime']);

		// Eliminar los más viejos
		$deleted = 0;
		for ($i = 0; $i < $toDelete; $i++) {
			if (unlink($files[$i]['path'])) {
				$deleted++;
			}
		}
		return $deleted;
	}

	private function ResolveFolder($key1, $key2, bool $create = false): string
	{
		$key1 = (string) $key1;
		$key2 = (string) $key2;
		if ($key2 !== '')
		{
			$folder = $this->path . "/" . $key1;
			if ($create)
				IO::EnsureExists($folder);
			return $folder;
		}
		else
		{
			return $this->path;
		}
	}

	private function ResolveFilename($key1, $key2, bool $create = false) : string
	{
		$key1 = (string)$key1;
		$key2 = (string)$key2;
		$folder = $this->ResolveFolder($key1, $key2, $create);
		if ($key2 !== '')
			return $folder . "/" . $key2 . ".txt";
		else
			return $folder . "/" . $key1 . ".txt";
	}

	private function ResolveFilenameRaw($key1, $key2, bool $create = false) : string
	{
		$key1 = (string) $key1;
		$key2 = (string) $key2;
		$folder = $this->ResolveFolder($key1, $key2, $create);
		if ($key2 !== '')
			return $folder . "/" . $key2 . ".raw";
		else
			return $folder . "/" . $key1 . ".raw";
	}

	public function PutDataIfMissing($key1, $key2, $value) : void
	{
		if (Context::Settings()->Cache()->Enabled === CacheSettings::Disabled || $this->HasData($key1, $key2))
			return;
		$this->PutData($key1, $key2, $value);
	}

	public function PutData($key1, $key2, $value) : void
	{
		if (Context::Settings()->Cache()->Enabled === CacheSettings::Disabled)
			return;

		$this->CheckLimits($key1, $key2);

		$file = $this->ResolveFilename($key1, $key2, true);
		IO::WriteAllText($file, $value);
	}

	public function PutRawData($key1, $key2, $value) : void
	{
		if (Context::Settings()->Cache()->Enabled === CacheSettings::Disabled)
			return;

		$file = $this->ResolveFilenameRaw($key1, $key2, true);
		IO::WriteAllText($file, $value);
	}
}

