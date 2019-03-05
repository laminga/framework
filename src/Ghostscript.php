<?php

namespace minga\framework;

class Ghostscript
{
	/**
	 * directorio con los binarios
	 *
	 * origen: https://www.ghostscript.com/download/gsdnld.html
	 */
	const GHOSTSCRIPT = "/gs";

	// dejo un margen por caracteres unicode
	// el máximo es en bytes pero los strings son multibyte.
	const MetadataMaxLen = 400;

	public static function Merge($coverFile, $originalFile, $targetFile, $title, $authors)
	{
		Profiling::BeginTimer();

		self::TruncateMetadata($title, $authors);

		$args = '-dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile='
			. $targetFile . ' ' . $coverFile . ' ' . $originalFile
			. ' -c "[/Producer(AAcademica.org)/Author '
			. self::EscapeUnicode2($authors) . ' /Title '
			. self::EscapeUnicode2($title) . ' /DOCINFO pdfmark"';

		IO::Delete($targetFile);

		if (self::RunGhostscript($args) == false)
			IO::Delete($targetFile);

		Profiling::EndTimer();
	}

	public static function TruncateMetadata(&$title, &$authors)
	{
		$lenTitle = strlen($title);
		if(strlen($authors) + $lenTitle > self::MetadataMaxLen)
		{
			if($lenTitle > self::MetadataMaxLen)
			{
				$authors = '';
				$title = Str::Substr($title, 0, self::MetadataMaxLen);
			}
			else
				$authors = Str::Substr($authors, 0, self::MetadataMaxLen - $lenTitle);
		}
	}

	public static function EscapeUnicode2($cad)
	{
		return '<FEFF' . strtoupper(bin2hex(iconv('UTF-8', 'UCS-2BE', $cad))) . '>';
	}

	public static function CreateRaw($file)
	{
		Profiling::BeginTimer();

		$targetFile = IO::GetTempFilename() . ".pdf";

		$args = "-dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile="
			. $targetFile . " -c .setpdfwrite -f " . $file;

		if (self::RunGhostscript($args) == false)
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

		$args = "-dNOPAUSE -dBATCH -sDEVICE=jpeg -dFirstPage=1 -dAlignToPixels=0 -dGridFitTT=2 -dLastPage=1 -dTextAlphaBits=4 -dGraphicsAlphaBits=4 -dJPEGQ=100 -r30 -sOutputFile=" . $targetFile . " -c .setpdfwrite -f " . $file;

		if (self::RunGhostscript($args) == false)
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
			throw new ErrorException("File not found for SuperExec ('" . $file. "').");

		if (Str::StartsWith($args, " ") == false) $args = " " . $args;
		exec($file . $args, $out, $returnCode);

		if ($returnCode == 126)
			throw new ErrorException("Execute permissions not available for SuperExec ('" . $file. "').");

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
		$bits = System::GetArchitecture();

		$exeFile = Context::Paths()->GetBinPath() . self::GHOSTSCRIPT . $bits;

		$out = self::SuperExec($exeFile, $args, $retCode);

		$extraInfo = "\n---------------\nExe: " . $exeFile . "\n Args:" . $args . "\n------------\n";

		if ($retCode == 1)
		{
			$text = "Ghostscript exited with unrecoverable error (error: " . $out . "). " . $extraInfo;
			$text = Str::Replace($text, "\n", '<br>');
			Log::HandleSilentException(new ErrorException($text));
			return false;
		}

		if (Str::Contains($out, "GPL Ghostscript") == false)
		{
			$text = "Ghostscript exited with unexpected output (retcode: " . $retCode . "). " . $extraInfo;
			$text = Str::Replace($text, "\n", '<br>');
			Log::HandleSilentException(new ErrorException($text));
			return false;
		}

		return true;
	}

}
