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
				$this->CallScript($job);
				$job['last_run'] = Date::FormattedDate(time());
				IO::WriteJson($file, $job, true);
			}
		}
	}

	private static function ShouldRun(array $job) : bool
	{
		if($job['enabled'] == false)
			return false;

		if(isset($job['last_run']))
			$lastRun = Date::FormattedDateToDateTime($job['last_run']);
		else
			$lastRun = Date::FormattedDateToDateTime($job['first_run']);

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

	private function CallScript(array $job) : void
	{
		$ret = System::RunCommandRaw(Context::Settings()->Servers()->PhpCli
			. ' ' . Context::Paths()->GetCronJobsScriptPath() . '/' . $job['script']);
		if($ret['return'] != 0)
			Log::HandleSilentException(new \Exception('Falló CallScript. Job: ' . json_encode($job) . '. Return: ' . json_encode($ret)));
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
}
