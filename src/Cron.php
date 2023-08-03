<?php

namespace minga\framework;

class Cron
{
	public function Run() : void
	{
		$files = IO::GetFiles(Context::Paths()->GetCronJobsPath(), '.json', true);
		foreach($files as $file)
		{
			$job = IO::ReadJson($file);
			if(self::ShouldRun($job))
			{
				$res = self::CallScript($job);
				self::SaveStatus($file, $job, $res);
			}
		}
	}

	public static function SaveStatus(string $file, array $job, string $result) : void
	{
		if (trim($result) == '')
			$result = 'OK';
		$job['last_run'] = Date::FormattedDate(time());
		$job['result'] = $result;
		IO::WriteJson($file, $job, true);
	}

	private static function ShouldRun(array $job) : bool
	{
		if($job['enabled'] == false)
			return false;

		if (isset($job['last_run']) == false)
			return true;

		$lastRun = Date::FormattedDateToDateTime($job['last_run']);

		$timeNextRun = $lastRun->add(self::GetInterval($job));
		return $timeNextRun <= new \DateTime();
	}

	public static function ShouldRunAny() : bool
	{
		$files = IO::GetFiles(Context::Paths()->GetCronJobsPath(), '.json', true);
		foreach($files as $file)
		{
			$job = IO::ReadJson($file);
			if(self::ShouldRun($job))
				return true;
		}
		return false;
	}

	public static function CallScript(array $job) : string
	{

		$log = IO::GetTempFilename() . '.queue.log';
		$out = [];
		$ret = System::Execute(Context::Settings()->Servers()->PhpCli, [Context::Paths()->GetCronJobsScriptPath() . '/' . $job['script'], 'log=' . $log], $out);

		$lines = implode("\n", $out);

		if($ret != 0)
			self::LogError($log, $job, $ret, $lines);
		else
			IO::Delete($log);

		return $lines;
	}

	private static function LogError(string $log, array $job, int $ret, string $lines) : void
	{
		$lastLine = self::TrySaveLastError($log);
		Log::HandleSilentException(new \Exception('Falló CallScript. Job: ' . json_encode($job)
			. "\nReturn: " . $ret
			. "\nLog file: " . $log
			. "\nLast line log: " . $lastLine
			. "\nResult lines: " . $lines));
	}

	private static function GetInterval(array $job) : \DateInterval
	{
		$unit = $job['unit'];

		$time = '';
		if($unit == 'I' || $unit == 'H')
			$time = 'T';

		if($unit == 'I')
			$unit = 'M';

		return new \DateInterval('P' . $time . $job['freq'] . $unit);
	}

	private static function TrySaveLastError(string $log) : string
	{
		if(file_exists($log) == false)
			return '';

		$lines = IO::ReadAllLines($log);
		if(count($lines) == 0)
			return '';

		$lastLine = str_replace("\\", "/", trim($lines[count($lines) - 1]));
		if(Str::EndsWith($lastLine, 'DONE')
			|| Str::Contains($lastLine, '/running/') == false
			|| file_exists($lastLine) == false)
		{
			return '';
		}

		IO::Move($lastLine, str_replace('/running/', '/queued/', $lastLine));
		return $lastLine;
	}
}
