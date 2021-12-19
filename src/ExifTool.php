<?php

namespace minga\framework;

class ExifTool
{
	public static function GetBinary() : string
	{
		$binPath = Context::Paths()->GetBinPath();
		return '"' . $binPath . '/exiftool/exiftool"';
	}

	public static function UpdateMetadata(string $file, string $title, string $authors) : bool
	{
		Profiling::BeginTimer();
		// Borra temporal anterior si existe
		IO::Delete($file . '_exiftool_tmp');
		$title = self::PrepareText($title);
		$authors = self::PrepareText($authors);

		$args = '-overwrite_original -L -Producer="AAcademica.org" -Author='
			. $authors . ' -Title=' . $title . ' ' . $file;

		$ret = self::Run($args);
		Profiling::EndTimer();
		return $ret;
	}

	private static function PrepareText(string $text) : string
	{
		$text = Str::Convert($text, 'ISO-8859-1', 'UTF-8', true, true);
		$text = str_replace(["\r", "\n"], ' ', $text);
		return escapeshellarg(trim($text));
	}

	private static function Run(string $args) : bool
	{
		$ret = System::RunCommandRaw(self::GetBinary() . ' ' . $args);
		if ($ret['return'] != 0)
		{
			$text = 'ExifTool exited with error (code: ' . $ret['return'] . ', error: ' . $ret['output'] . '). '
				. "\n---------------\nExe: " . self::GetBinary() . "\n Args:" . $args . "\n------------\n";
			$text = Str::Replace($text, "\n", '<br>');
			Log::HandleSilentException(new ErrorException($text));
			return false;
		}
		return true;
	}

}
