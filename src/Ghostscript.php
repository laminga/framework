<?php

namespace minga\framework;

class Ghostscript
{
	/**
	 * directorio con los binarios
	 *
	 * origen: https://www.ghostscript.com/download/gsdnld.html
	 */
	const BIN_PATH = "/cgi-bin";

	const GHOSTSCRIPT = "/gs";

	const GHOSTSCRIPT_ARGS = "-nopgbrk -enc UTF-8";

	public static function Merge($coverFile, $originalFile, $targetFile, $title, $authors)
	{
		Profiling::BeginTimer();

		$args = "-dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=$targetFile ";
		$args .= $coverFile ." ";
		$args .= $originalFile ." ";

		$args .= " -c \"[/Producer(AAcademica.org)/Author "
			. self::escapeUnicode2($authors) ." /Title " . self::escapeUnicode2($title) . " /DOCINFO pdfmark\"";

		IO::Delete ($targetFile);

		if (!self::RunGhostscript($args))
			IO::Delete($targetFile);

		Profiling::EndTimer();
	}

	private static function escapeUnicode2($cad)
	{
		return '<FEFF'.strtoupper(bin2hex(iconv('UTF-8', 'UCS-2BE', $cad))) . ">";

	}

	public static function CreateRaw($file)
	{
		Profiling::BeginTimer();

		$targetFile = IO::GetTempFilename() . ".pdf";

		$args = "-dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=" . $targetFile . " -c .setpdfwrite -f ";
		$args .= $file;

		if (!self::RunGhostscript($args))
			IO::Delete($targetFile);

		Profiling::EndTimer();

		if (file_exists($targetFile))
			return $targetFile;
		else
			return null;
	}


	public static function CreateThumbnail($file)
	{
		Profiling::BeginTimer();

		$targetFile = IO::GetTempFilename() . ".jpg";

		$args = "-dNOPAUSE -dBATCH -sDEVICE=jpeg -dFirstPage=1 -dAlignToPixels=0 -dGridFitTT=2 -dLastPage=1 -dTextAlphaBits=4 -dGraphicsAlphaBits=4 -dJPEGQ=100 -r30 -sOutputFile=" . $targetFile . " -c .setpdfwrite -f ";
		$args .= $file;

		if (!self::RunGhostscript($args))
			IO::Delete($targetFile);

		Profiling::EndTimer();

		if (file_exists($targetFile))
			return $targetFile;
		else
			return null;
	}

	//TODO: Buscar una mejor clase para poner este método.
	private static function SuperExec($file, $args, &$returnCode = null, $returnFirstLineOnly = false)
	{
		if (file_exists($file) == false)
			throw new \Exception("File not found for SuperExec ('" . $file. "').");

		if (Str::StartsWith($args, " ") == false) $args = " " . $args;
		exec($file . $args, $out, $returnCode);

		if ($returnCode == 126)
			throw new \Exception("Execute permissions not available for SuperExec ('" . $file. "').");

		if (is_array($out) == false || sizeof($out) == 0)
			$ret = "";
		else
		{
			if ($returnFirstLineOnly)
				$ret = $out[0];
			else
				$ret = implode("\n", $out);
		}
		return $ret;
	}

	private static function RunGhostscript($args)
	{
		$bits = self::GetArchitecture();

		$exeFile = Context::Paths()->GetRoot(). self::BIN_PATH . self::GHOSTSCRIPT. $bits;

		$out = self::SuperExec($exeFile, $args, $retCode);

		$extraInfo = "\n---------------\nExe: " . $exeFile . "\n Args:" . $args . "\n------------\n";

		if ($retCode == 1)
		{
			$text = "Ghostscript exited with unrecoverable error (error: " . $out . "). " . $extraInfo;
			$text = Str::Replace($text, "\n", '<br>');
			Log::HandleSilentException(new \Exception($text));
			return false;
		}

		if (Str::Contains($out, "GPL Ghostscript") == false)
		{
			$text = "Ghostscript exited with unexpected output (retcode: " . $retCode . "). " . $extraInfo;
			$text = Str::Replace($text, "\n", '<br>');
			Log::HandleSilentException(new \Exception($text));
			return false;
		}

		return true;
	}

	private static function GetArchitecture()
	{
		switch(PHP_INT_SIZE)
		{
			case 4:
				return "32"; //32 bit version of PHP
			case 8:
				return "64"; //64 bit version of PHP
			default:
				throw new \Exception('PHP_INT_SIZE is '.PHP_INT_SIZE);
		}
	}
}
