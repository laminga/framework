<?php

namespace minga\framework;

class FatalErrorSender
{
	public static function SendErrorLog()
	{
		$path = Context::Paths()->GetRoot();
		$sentPath = Context::Paths()->GetLogLocalPath() . '/' . Log::FatalErrorsPath . '/sent';
		IO::EnsureExists($sentPath);

		$files = IO::GetFilesStartsWithAndExt($path, 'error_log', '', true, true);
		foreach($files as $file)
		{
			if(basename($file) != 'error_log')
				continue;

			Log::PutToMail('Error file: ' . $file . '<br><br>' . nl2br(file_get_contents($file)), true);
			IO::Move($file, $sentPath . '/' . Date::FormattedArNow() . '-error_log.txt');
			sleep(1);
		}
		echo 'Procesados (error_log): ' . count($files) . "\n";
	}

	public static function SendFatalErrors()
	{
		$path = Context::Paths()->GetLogLocalPath() . '/' . Log::FatalErrorsPath;
		$sentPath = $path . '/sent';
		IO::EnsureExists($sentPath);

		$files = IO::GetFilesFullPath($path, '.txt');
		foreach($files as $file)
		{
			Log::PutToMail('Error file: ' . $file . '<br><br>' . file_get_contents($file), true);
			IO::Move($file, $sentPath . '/' . basename($file));
			sleep(1);
		}
		echo 'Procesados (' . Log::FatalErrorsPath . '): ' . count($files) . "\n";
	}
}
