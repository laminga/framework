<?php

namespace minga\framework;

class Ghostscript
{
	/**
	 * directorio con los binarios
	 *
	 * origen: https://www.ghostscript.com/download/gsdnld.html
	 */
	public const GHOSTSCRIPT = "/gs";

	// Hace falta limitar por el encoding que usa gs para
	// metadata que multiplica el tamaño del texto por mucho.
	public const MetadataMaxLen = 400;

	public static function Merge(string $coverFile, string $originalFile, string $targetFile, string $title, string $authors) : bool
	{
		Profiling::BeginTimer();
		self::TruncateMetadata($title, $authors);

		$args = '-dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile="'
			. $targetFile . '" "' . $coverFile . '" "' . $originalFile
			. '" -c "[/Producer(AAcademica.org)/Author '
			. self::EscapeUnicode2($authors) . ' /Title '
			. self::EscapeUnicode2($title) . ' /DOCINFO pdfmark"';

		IO::Delete($targetFile);
		$ret = self::Run($args);
		if ($ret == false)
			IO::Delete($targetFile);
		Profiling::EndTimer();
		return $ret;
	}

	private static function TruncateMetadata(string &$title, string &$authors) : void
	{
		$lenTitle = strlen($title);
		if(strlen($authors) + $lenTitle > self::MetadataMaxLen)
		{
			if($lenTitle > self::MetadataMaxLen)
			{
				$authors = '';
				$title = mb_strcut($title, 0, self::MetadataMaxLen);
			}
			else
				$authors = mb_strcut($authors, 0, self::MetadataMaxLen - $lenTitle);
		}
	}

	public static function EscapeUnicode2(string $cad) : string
	{
		return '<FEFF' . strtoupper(bin2hex(iconv('UTF-8', 'UCS-2BE', $cad))) . '>';
	}

	public static function CreateRaw(string $file) : ?string
	{
		Profiling::BeginTimer();
		try
		{
			$targetFile = IO::GetTempFilename() . ".pdf";

			$args = '-dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile="'
				. $targetFile . '" -c .setpdfwrite -f "' . $file . '"';

			if (self::Run($args) == false)
				IO::Delete($targetFile);

			Profiling::EndTimer();

			if (file_exists($targetFile))
				return $targetFile;

			return null;
		}
		finally
		{
			Profiling::EndTimer();
		}
	}

	public static function CreateThumbnail(string $file, ?string $targetFile = null) : ?string
	{
		Profiling::BeginTimer();
		try
		{
			if ($targetFile == null)
				$targetFile = IO::GetTempFilename() . ".jpg";

			$args = '-dNOPAUSE -dBATCH -sDEVICE=jpeg -dFirstPage=1 -dAlignToPixels=0 -dGridFitTT=2'
				. ' -dLastPage=1 -dTextAlphaBits=4 -dGraphicsAlphaBits=4 -dJPEGQ=75 -r30'
				. ' -sOutputFile="' . $targetFile . '" -f "' . $file . '"';

			if (self::Run($args) == false)
				IO::Delete($targetFile);

			if (file_exists($targetFile))
				return $targetFile;

			return null;
		}
		finally
		{
			Profiling::EndTimer();
		}
	}

	private static function Run(string $args) : bool
	{
		$bits = System::GetArchitecture();

		$exeFile = Context::Paths()->GetBinPath() . self::GHOSTSCRIPT . $bits;

		$out = System::RunCommandGS($exeFile, $args, $retCode);

		$extraInfo = "\n---------------\nExe: " . $exeFile . "\n Args:" . $args . "\n------------\n";

		if ($retCode == 1)
		{
			$text = 'Ghostscript terminó con un error irrecuperable (error: ' . $out . "). " . $extraInfo;
			$text = Str::Replace($text, "\n", '<br>');
			Log::HandleSilentException(new ErrorException($text));
			return false;
		}

		if (Str::Contains($out, "GPL Ghostscript") == false)
		{
			$text = 'Ghostscript terminó de modo inesperado (retcode: ' . $retCode . "). " . $extraInfo;
			$text = Str::Replace($text, "\n", '<br>');
			Log::HandleSilentException(new ErrorException($text));
			return false;
		}
		return true;
	}
}
