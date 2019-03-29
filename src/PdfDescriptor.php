<?php

namespace minga\framework;

class PdfDescriptor
{
	/**
	 * directorio con los binarios
	 */
	const PDF_TO_TEXT = "./pdftotext";

	/**
	 * sin caracter para saltos de página y utf-8
	 */
	const PDF_TO_TEXT_ARGS = "-nopgbrk -enc UTF-8";

	const PDF_INFO = "./pdfinfo";
	const PDF_INFO_ARGS = "-enc UTF-8";

	public static function Truncate64k($cad)
	{
		if (strlen($cad) > 65534)
			return mb_strcut($cad, 0, 65534);
		else
			return $cad;
	}

	public static function GetText($file)
	{
		try
		{
			Profiling::BeginTimer();
			$ret = implode(" ", self::RunPdfToText($file));
			$ret = trim(preg_replace('/\s+/', ' ', $ret));
			return $ret;
		}
		catch(\Exception $e)
		{
			Log::HandleSilentException($e);
			// throw new \Exception('No se puede leer el texto del pdf.');
		}
		finally
		{
			Profiling::EndTimer();
		}
	}
	public static function GetPages($file)
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
			$data = explode(":", $line);
			if(count($data) < 2)
				continue;

			if(trim($data[0]) == "Pages")
				$ret['pages'] = (int)trim($data[1]);
			else if(trim($data[0]) == "Encrypted")
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
		$command = self::PDF_TO_TEXT.$bits.
			' '.self::PDF_TO_TEXT_ARGS.
			' '.escapeshellarg($file).' -';
		return System::RunCommandOnPath($command);
	}

	private static function RunPdfInfo($file)
	{
		$bits = System::GetArchitecture();
		$command = self::PDF_INFO.$bits.
			' '.self::PDF_INFO_ARGS.
			' '.escapeshellarg($file);
		return System::RunCommandOnPath($command);
	}

}
