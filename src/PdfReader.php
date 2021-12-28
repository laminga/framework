<?php

namespace minga\framework;

use minga\classes\cache\PdfHtmlCache;
use minga\classes\cache\PdfTextCache;
use rodrigoq\pdftohtml\PdfToHtml;
use rodrigoq\pdftoinfo\PdfInfo;
use rodrigoq\pdftotext\PdfToText;

class PdfReader
{
	const MAX_LENGTH = 65534; // en bytes
	/**
	 * binarios
	 */
	const PDF_INFO = './pdfinfo';
	const PDF_TO_HTML = './pdftohtml';
	const PDF_TO_TEXT = './pdftotext';

	//sin caracter para saltos de página y utf-8
	const PDF_TO_TEXT_ARGS = '-nopgbrk -enc UTF-8';
	const PDF_INFO_ARGS = '-enc UTF-8';
	const PDF_TO_HTML_ARGS_FIRST_PAGE = '-f 1 -l 1';
	const PDF_TO_HTML_ARGS = '-nofonts';

	public static function Truncate64k(string $cad) : string
	{
		if (strlen($cad) > self::MAX_LENGTH)
			return mb_strcut($cad, 0, self::MAX_LENGTH);

		return $cad;
	}

	public static function GetText(string $file, bool $truncate = true, bool $removeSpaces = true) : string
	{
		Profiling::BeginTimer();

		$sep = "\n";
		if($removeSpaces)
			$sep = ' ';

		$text = implode($sep , self::RunPdfToText($file));
		if($removeSpaces)
			$text = preg_replace('/\s+/', ' ', $text);

		// Remueve caracter inválido: 0xf0b7
		$text = str_replace("", "\n", $text);

		if($truncate)
			$text = self::Truncate64k(trim($text));

		Profiling::EndTimer();
		return trim($text);
	}

	public static function GetHtml(string $file, bool $firstPageOnly = false) : string
	{
		Profiling::BeginTimer();

		$html = self::RunPdfToHtml($file, $firstPageOnly);
		//Sobre el resultado ejecutar esto si se quiere
		//el texto en una sola línea, sin espacios extra.
		//trim(preg_replace('/\s+/', ' ', $text));

		Profiling::EndTimer();
		return $html;
	}

	public static function GetPageCount(string $file) : int
	{
		$info = self::GetMetadataInfo($file);
		return (int)$info['pages'];
	}

	public static function GetMetadataInfo(string $file) : array
	{
		$ret = ['pages' => 0, 'encrypted' => false];
		$lines = self::GetInfo($file);
		foreach($lines as $line)
		{
			$data = explode(':', $line);
			if(count($data) < 2)
				continue;

			if(trim($data[0]) == 'Pages')
				$ret['pages'] = (int)trim($data[1]);
			else if(trim($data[0]) == 'Encrypted')
			{
				$ret['encrypted'] =
					Str::StartsWith(trim($data[1]), 'yes');
			}

		}
		return $ret;
	}

	public static function GetInfo(string $file) : array
	{
		try
		{
			Profiling::BeginTimer();
			$ret = self::RunPdfInfo($file);
			Profiling::EndTimer();
			return $ret;
		}
		catch(\Exception $e)
		{
			Log::HandleSilentException($e);
			// throw new \Exception('No se puede leer el archivo pdf. Archivo dañado');
		}
		return [];
	}

	private static function RunPdfToText(string $file) : array
	{
		$command = self::GetPdfToTextCommand($file, self::PDF_TO_TEXT_ARGS, $path);
		return System::RunCommandOnPath($command, $path);
	}

	private static function RunPdfInfo(string $file) : array
	{
		$command = self::GetPdfInfoCommand($file, self::PDF_INFO_ARGS, $path);
		return System::RunCommandOnPath($command, $path);
	}

	private static function RunPdfToHtml(string $file, bool $firstPageOnly) : string
	{
		$outPath = '';
		try
		{
			$args = self::PDF_TO_HTML_ARGS;
			if ($firstPageOnly)
				$args = self::PDF_TO_HTML_ARGS_FIRST_PAGE;

			$outPath = IO::GetTempDir();
			IO::RemoveDirectory($outPath);
			$command = self::GetPdfToHtmlCommand($file, $args, $outPath, $path);
			System::RunCommandOnPath($command, $path);
			$files = IO::GetFilesStartsWithAndExt($outPath, 'page', 'html', true);
			natsort($files);
			$text = '';
			$first = true;
			foreach($files as $file)
			{
				$part = file_get_contents($file);
				if($first == false)
					$part = str_replace(["<html>", "<head>", "</head>", "<body>"], "", $part);
				$first = false;
				$part = str_replace(["</body>", "</html>"], "", $part);
				$text .= $part;
			}

			return trim($text . "</body></html>");

		}
		catch(\Exception $e)
		{
			Log::HandleSilentException($e);
			return '';
		}
		finally
		{
			IO::RemoveDirectory($outPath);
		}
	}

	private static function GetPdfToTextCommand(string $file, string $args, ?string &$path) : string
	{
		$ext = '';
		if(System::IsWindows())
			$ext = '.exe';

		if(class_exists(PdfToText::class))
		{
			$bin = './' . PdfToText::GetBin();
			$path = PdfToText::GetPath();

			return $bin . $ext . ' ' . $args
				. ' ' . escapeshellarg($file) . ' -';
		}

		$path = null;
		$bits = System::GetArchitecture();
		return self::PDF_TO_TEXT . $bits . $ext
			. ' ' . $args
			. ' ' . escapeshellarg($file) . ' -';
	}


	private static function GetPdfInfoCommand(string $file, string $args, ?string &$path) : string
	{
		$ext = '';
		if(System::IsWindows())
			$ext = '.exe';

		if(class_exists(PdfInfo::class))
		{
			$bin = './' . PdfInfo::GetBin();
			$path = PdfInfo::GetPath();

			return $bin . $ext . ' ' . $args
				. ' ' . escapeshellarg($file);
		}

		$path = null;
		$bits = System::GetArchitecture();
		return self::PDF_INFO . $bits . $ext
			. ' ' . $args
			. ' ' . escapeshellarg($file);
	}

	private static function GetPdfToHtmlCommand(string $file, string $args, string $outPath, ?string &$path) : string
	{
		$ext = '';
		if(System::IsWindows())
			$ext = '.exe';

		if(class_exists(PdfToHtml::class))
		{
			$bin = './' . PdfToHtml::GetBin();
			$path = PdfToHtml::GetPath();

			return $bin . $ext . ' ' . $args
				. ' ' . escapeshellarg($file)
				. ' ' . escapeshellarg($outPath);
		}

		$path = null;
		$bits = System::GetArchitecture();
		return self::PDF_TO_HTML . $bits . $ext
			. ' ' . $args
			. ' ' . escapeshellarg($file)
			. ' ' . escapeshellarg($outPath);
	}

}


