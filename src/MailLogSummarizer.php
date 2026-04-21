<?php

namespace minga\framework;

class MailLogSummarizer
{
	public static function GetTotals(string $month = '') : array
	{
		$month = self::GetMonth($month);
		$file = Performance::ResolveFilenameTotalGroupedEmails($month);
		if(file_exists($file))
			return self::GetFromFile($file);
		return [];
	}

	public static function GetDate(string $month = '') : string
	{
		$month = self::GetMonth($month);
		$file = Performance::ResolveFilenameTotalGroupedEmails($month);
		if(file_exists($file) == false)
			return '-';
		$date = IO::FileMTime($file);
		$curr = new \DateTime('@' . $date);

		$last = \DateTime::createFromFormat("!Y-m-d", $month . '-01');
		$last->modify("+1 month");

		if($curr > $last)
			return $last->modify("-1 second")->format("d/m/Y H:i:s") . " (GMT-3)";

		return Date::UserFormattedAr($date);
	}

	private static function GetMonth(string $month) : string
	{
		if (strlen($month) != 7 || substr($month, 4, 1) != '-')
			return Date::GetLogMonthFolder();
		$y = (int)substr($month, 0, 4);
		$m = (int)substr($month, 5, 2);
		if ($y < 2000 || $m < 1 || $m > 12)
			return Date::GetLogMonthFolder();

		return $month;
	}

	private static function GetFromFile(string $file) : array
	{
		$text = htmlspecialchars(file_get_contents($file));
		$lines = explode("\n", trim($text));
		$ret = ['Tipo' => ['Cantidad']];

		foreach($lines as $line)
		{
			$parts = explode('=', $line);
			if($parts[0] != 'Total')
				$ret[$parts[0]] = [$parts[1]];
			else
				$ret['<b>Total</b>'] = ["<b>" . $parts[1] . "</b>"];
		}
		return $ret;
	}

	public static function doCalculate(string $month = '') : array
	{
		//Tarda...
		set_time_limit(600);

		$month = self::GetMonth($month);
		$path = Context::Paths()->GetLogLocalPath(Log::MailsPath, $month);
		if(file_exists($path) == false)
			return [];

		$res = [];
		$dir = new \DirectoryIterator($path);
		foreach ($dir as $fi)
		{
			if ($fi->isDot())
				continue;

			$f = fopen($fi->getPathname(), 'r');
			if($f === false)
				continue;

			$line = fgets($f);
			if(Str::StartsWith($line, "Type: "))
			{
				$parts = explode(":", $line);
				if(isset($res[trim($parts[1])]))
					$res[trim($parts[1])]++;
				else
					$res[trim($parts[1])] = 1;
			}
			else
			{
				$line = fgets($f); // Se descarta...
				$line = fgets($f); // Acá está el subject.
				if(Str::StartsWith($line, "Subject: "))
				{
					$parts = explode(":", $line);
					$key = self::GetGroup(trim($parts[1]));
					if(isset($res[$key]))
						$res[$key]++;
					else
						$res[$key] = 1;
				}
				else
					throw new \Exception("Archivo de log de mail sin subject: " . $fi->getPathname());
			}
			fclose($f);
		}
		arsort($res);
		$res['Total'] = array_sum($res);
		return $res;
	}

	public static function Calculate(string $month = '') : void
	{
		$month = self::GetMonth($month);
		$res = self::doCalculate($month);
		if(empty($res))
			return;

		$text = '';
		foreach($res as $k => $v)
			$text .= $k . '=' . $v . "\n";

		Performance::SaveTotalGroupedEmails($text, $month);
	}

	private static function GetGroup(string $subject) : string
	{
		if(Str::Contains($subject, "Javascript"))
			return "JavascriptError";
		if(Str::Contains($subject, "Fatal"))
			return "FatalError";
		if(Str::Contains($subject, "Error"))
			return "Error";
		if(Str::StartsWith($subject, "Recuperación"))
			return "ForgotPassword";
		if(Str::StartsWith($subject, "ALERTA ADMINISTRATIVA"))
			return "AlertaAdministrativa";
		if(Str::Contains($subject, "EventMessage"))
			return "EventMessage";
		if(Str::Contains($subject, "AccountActivation"))
			return "AccountActivation";
		if(Str::Contains($subject, "ProfileNew"))
			return "ProfileNew";
		if(Str::StartsWith($subject, "Feedback"))
			return "Feedback";
		if(Str::Contains($subject, "ProfileMessage"))
			return "ProfileMessage";
		if(Str::Contains($subject, "EventActivityPermission"))
			return "EventActivityPermission";
		if(Str::StartsWith($subject, "[PHPUNIT]"))
			return "Phpunit";
		if(Str::Contains($subject, "EventNew"))
			return "EventNew";
		if(Str::Contains($subject, "ProfileInvitation"))
			return "ProfileInvitation";
		if(Str::Contains($subject, "InstitutionNew"))
			return "InstitutionNew";
		if(Str::Contains($subject, "InstitutionMessage"))
			return "InstitutionMessage";
		if(Str::Contains($subject, "Permission"))
			return "Permission";
		if(Str::Contains($subject, "EventActivitySubmissionAdmin"))
			return "EventActivitySubmissionAdmin";

		return "Otro";
	}
}
