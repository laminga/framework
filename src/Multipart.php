<?php

namespace minga\framework;

class Multipart
{

	/**
	 * Split the zip archive.
	 * @param string $i The zip archive.
	 * @param integer $s The max size for the parts.
	 * @return integer Return the number of parts created.
	 */
	public static function Split($i, $s)
	{
		$fs = filesize($i);
		$p = 1;
		$date = IO::FileMTime($i);
		if($date === false)
			$date = time();
		for($c = 0; $c < $fs; $c = $c + $s)
		{
			$fileData = file_get_contents($i, false, null, $c, $s);
			$fn = $i . "." . $p . ".part";
			file_put_contents($fn, $fileData);
			$p++;
			unset($fileData);
			touch($fn, $date);
		}
		IO::Delete($i);
		return $p - 1;
	}

	/**
	 * Decompact the zip archive.
	 * @param string $i The zip archive (*.zip).
	 * @param string $o The directory name for extract.
	 * @param integer $p Number of parts of the zip archive.
	 * @return boolean Return true for success or false for fail.
	 */
	public static function Unzip($i, $o, $p = 0)
	{
		$success = true;
		if($p > 0)
			$success = self::Merge($i, $p);

		if($success == false)
			return false;

		$zp = new \ZipArchive();
		$zp->open($i);
		if($zp->extractTo($o))
		{
			$zp->close();
			unset($zp);
			IO::Delete($i);
			return true;
		}
		else
			return false;
	}

	/**
	 * Merge the parts of zip archive.
	 * @param string $i The zip archive (*.zip).
	 * @param integer $p Number of parts of the zip archive.
	 * @return boolean Return true for success or false for fail.
	 */
	public static function Merge($i, $p)
	{
		for($c = 1; $c <= $p; $c++)
		{
			$data = file_get_contents($i . $c . "part");
			file_put_contents($i, $data, FILE_APPEND);
			unset($data);
		}
		return true;
	}
}
