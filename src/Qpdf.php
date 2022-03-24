<?php

namespace minga\framework;

class Qpdf
{
	public static function Merge(string $coverFile, string $originalFile, string $targetFile, string $title, string $authors) : bool
	{
		Profiling::BeginTimer();
		try
		{
			$args = '--empty "' . $targetFile . '" --pages "' . $coverFile . '" "' . $originalFile . '" --';

			IO::Delete($targetFile);
			if (self::Run($args) == false)
			{
				IO::Delete($targetFile);
				return false;
			}
			ExifTool::UpdateMetadata($targetFile, $title, $authors);
			return true;
		}
		finally
		{
			Profiling::EndTimer();
		}
	}

	private static function Run(string $args) : bool
	{
		$bits = System::GetArchitecture();

		$exe = Context::Paths()->GetBinPath() . '/qpdf/' . $bits . '/qpdf';

		$ret = System::RunCommandRaw($exe . ' ' . $args);

		if ($ret['return'] != 0)
		{
			$text = 'Qpdf salió con error (código: ' . $ret['return'] . ', error: ' . $ret['output'] . '. '
				. "\n---------------\nExe: " . $exe . "\n Args:" . $args . "\n------------\n";
			$text = Str::Replace($text, "\n", '<br>');
			Log::HandleSilentException(new ErrorException($text));
			return false;
		}
		return true;
	}
}
