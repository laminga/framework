<?php

namespace minga\framework;

use minga\classes\cache\PdfHtmlCache;
use minga\classes\cache\PdfTextCache;

class PdfReader
{
	/**
	 * binarios
	 */
	const PDF_INFO = './pdfinfo';
	const PDF_TO_HTML = './pdftohtml';
	const PDF_TO_TEXT = './pdftotext';

	//sin caracter para saltos de página y utf-8
	const PDF_TO_TEXT_ARGS = '-nopgbrk -enc UTF-8';
	const PDF_INFO_ARGS = '-enc UTF-8';
	const PDF_TO_HTML_ARGS_FIRST_PAGE = '-enc UTF-8 -stdout -f 1 -l 1 -noframes -i ';
	const PDF_TO_HTML_ARGS = '-enc UTF-8 -stdout -noframes -i ';

	public static function Truncate64k($cad)
	{
		if (strlen($cad) > 65534)
			return mb_strcut($cad, 0, 65534);
		else
			return $cad;
	}

	public static function GetText($file, $truncate = true)
	{
		Profiling::BeginTimer();

		$text = implode(' ', self::RunPdfToText($file));
		$text = trim(preg_replace('/\s+/', ' ', $text));

		if($truncate)
			$text = self::Truncate64k($text);

		Profiling::EndTimer();
		return $text;
	}

	public static function GetHtml($file, $firstPageOnly = false)
	{
		Profiling::BeginTimer();

		$html = implode("\n", self::RunPdfToHtml($file, $firstPageOnly));
		//Sobre el resultado ejecutar esto si se quiere
		//el texto en una sola línea, sin espacios extra.
		//trim(preg_replace('/\s+/', ' ', $text));

		Profiling::EndTimer();
		return $html;
	}

	public static function GetPageCount($file)
	{
		$info = self::GetMetadataInfo($file);
		return $info['pages'];
	}

	public static function GetMetadataInfo($file)
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

	public static function GetInfo($file)
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

	private static function RunPdfToText($file)
	{
		$bits = System::GetArchitecture();
		$command = self::PDF_TO_TEXT . $bits
			. ' ' . self::PDF_TO_TEXT_ARGS
			. ' ' . escapeshellarg($file) . ' -';
		return System::RunCommandOnPath($command);
	}

	private static function RunPdfInfo($file)
	{
		$bits = System::GetArchitecture();
		$command = self::PDF_INFO . $bits
			. ' ' . self::PDF_INFO_ARGS
			. ' ' . escapeshellarg($file);
		return System::RunCommandOnPath($command);
	}

	private static function RunPdfToHtml($file, $firstPageOnly)
	{
		try
		{
			$bits = System::GetArchitecture();
			if(System::IsOnIIS())
				$bits .= '.exe';

			$args = self::PDF_TO_HTML_ARGS;
			if ($firstPageOnly)
				$args = self::PDF_TO_HTML_ARGS_FIRST_PAGE;

			$command = self::PDF_TO_HTML . $bits
				. ' ' . escapeshellarg($args)
				. ' ' . escapeshellarg($file);

			return System::RunCommandOnPath($command);
		}
		catch(\Exception $e)
		{
			Log::HandleSilentException($e);
			return [];
		}
	}
}
