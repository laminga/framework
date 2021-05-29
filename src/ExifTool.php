<?php

namespace minga\framework;

class ExifTool
{
	public static function UpdateMetadata(string $file, string $title, string $authors) : bool
	{
		Profiling::BeginTimer();
		try
		{
			$title = self::PrepareText($title);
			$authors = self::PrepareText($authors);

			$args = '-overwrite_original -L -Producer="AAcademica.org" -Author="'
				. $authors . '" -Title="' . $title . '" "' . $file . '"';

			return self::Run($args);
		}
		finally
		{
			Profiling::EndTimer();
		}
	}

	private static function PrepareText(string $text) : string
	{
		$text = Str::Convert($text, 'ISO-8859-1', 'UTF-8', true, true);
		if (Str::Contains($text, '"'))
			$text = Str::Replace($text, '"', '\"');
		if (Str::Contains($text, "\r"))
			$text = Str::Replace($text, "\r", ' ');
		if (Str::Contains($text, "\n"))
			$text = Str::Replace($text, "\n", ' ');
		return $text;
	}

	private static function Run(string $args) : bool
	{
		$exe = Context::Paths()->GetBinPath() . '/exiftool/exiftool';

		$ret = System::RunCommandRaw($exe . ' ' . $args);
		if ($ret['return'] != 0)
		{
			$text = 'ExifTool exited with error (code: ' . $ret['return'] . ', error: ' . $ret['output'] . '). '
				. "\n---------------\nExe: " . $exe . "\n Args:" . $args . "\n------------\n";
			$text = Str::Replace($text, "\n", '<br>');
			Log::HandleSilentException(new ErrorException($text));
			return false;
		}
		return true;
	}

}
