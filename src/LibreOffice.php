<?php

namespace minga\framework;

class LibreOffice
{
	public static function ConvertWordToPdf($srcFile, $dstPath) : void
	{
		$command = self::GetExecutable() . ' --headless --convert-to pdf --outdir "'
			. $dstPath . '" --norestore --nolockcheck "' . $srcFile . '" 2>&1';
		$ret = System::RunCommandRaw($command);

		if($ret['return'] != 0 || Str::StartsWith($ret['lastLine'], "Error: "))
			throw new ErrorException("Error al convertir archivo de Word a pdf.\n" . print_r($ret, true));

		$dstFile = $dstPath . '/' . Extensions::ChangeExtension(basename($srcFile), 'pdf');
		if(file_exists($dstFile) == false)
			throw new ErrorException("Error al convertir archivo de Word a pdf. Archivo no creado.\n" . print_r($ret, true));
	}

	public static function GetExecutable() : string
	{
		if(System::IsWindows())
			return Context::Settings()->WordConversion()->LibreOfficeWindowsPath;
		return "libreoffice";
	}

	public static function CheckLibreOffice() : void
	{
		$msg = 'con "sudo apt install libreoffice"';
		if(System::IsWindows())
			$msg = "y/o setear el path en Context::Settings()->WordConversion()->LibreOfficeWindowsPath";
		$ret = System::RunCommandRaw(self::GetExecutable() . ' --version 2>&1');
		if($ret['return'] != 0)
		{
			throw new ErrorException("Error al ejecutar libreoffice, puede ser que falte instalarlo "
				. $msg . "\nEjecutable: '" . self::GetExecutable() . "' Error: " . print_r($ret, true));
		}
	}
}
