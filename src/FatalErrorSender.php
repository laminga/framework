<?php

namespace minga\framework;

class FatalErrorSender
{
	private static function GetFatalLogPath()
	{
		return Context::Paths()->GetLogLocalPath() . '/' . Log::FatalErrorsPath;
	}
	private static function ResolveFataLogSentPath()
	{
		$ret = self::GetFatalLogPath() . '/sent';
		IO::EnsureExists($ret);
		return $ret;
	}

	public static function SendErrorLog($silent = false) : void
	{
		$path = self::GetFatalLogPath();
		$file = $path . '/error_log';
		$found = false;
		if (file_exists($file))
		{
			Log::PutToMailFatal('Error file: ' . $file . '<br><br>' . nl2br(file_get_contents($file)));
			$sentPath = self::ResolveFataLogSentPath();
			IO::Move($file, $sentPath . '/' . Date::FormattedArNow() . '-error_log.txt');
			$found = true;
		}
		if (!$silent && $found)
			echo "Procesado (error_log)\n";
	}

	public static function SendFatalErrors($silent = false) : void
	{
		$path = Context::Paths()->GetLogLocalPath() . '/' . Log::FatalErrorsPath;
		$sentPath = self::ResolveFataLogSentPath();

		$files = IO::GetFilesFullPath($path, '.txt');
		foreach($files as $file)
		{
			Log::PutToMailFatal('Error file: ' . $file . '<br><br>' . file_get_contents($file));
			IO::Move($file, $sentPath . '/' . basename($file));
		}

		if (!$silent)
			echo 'Procesados (' . Log::FatalErrorsPath . '): ' . count($files) . "\n";
	}
}
