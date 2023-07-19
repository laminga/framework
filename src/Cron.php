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
				$result = $this->CallScript($job);
				self::SaveStatus($file, $job, $result);
			}
		}
	}

    public static function SaveStatus(string $file, array $job, string $result): void
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

        if (isset($job['last_run']))
            $lastRun = Date::FormattedDateToDateTime($job['last_run']);
        else
            return true;

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

	private function CallScript(array $job): string
    {
        $cli = Context::Settings()->Servers()->PhpCli;
        $linesOut = [];
        $ret = System::Execute(
            $cli,
            [Context::Paths()->GetCronJobsScriptPath() . '/' . $job['script']], $linesOut);
		if($ret['return'] != 0)
			Log::HandleSilentException(new \Exception('Falló CallScript. Job: ' . json_encode($job) . '. Return: ' . json_encode($linesOut)));
        $result = implode("\n", $linesOut);
		return $result;
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
