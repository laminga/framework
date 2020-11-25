<?php

namespace minga\framework;

use minga\framework\GeoIp;
use minga\framework\locking\TrafficLock;

class Traffic
{
	const C_FACTOR = 4;

	public static function RegisterIP(string $ip, string $userAgent = '', bool $isMegaUser = false) : void
	{
		Profiling::BeginTimer();
		try
		{
			self::Save($ip, $userAgent, $isMegaUser);
		}
		catch(\Exception $e)
		{
			Log::HandleSilentException($e);
		}
		finally
		{
			Profiling::EndTimer();
		}
	}

	private static function Save(string $ip, string $userAgent, bool $isMegaUser) : void
	{
		$addr = inet_pton($ip);
		if($addr === false)
			throw new \Exception('Invalid address.');

		$chars = str_split($addr);

		$set = self::NumberToFile(ord($chars[count($chars) - 1]) / self::C_FACTOR + 1);

		$device = 'n/d'; // comentado por performance self::GetDevice();
		$lock = new TrafficLock($set);

		$lock->LockWrite();
		$hits = self::SaveIpHit($set, $ip, $device);
		$lock->Release();

		if($isMegaUser)
			return;

		$limit = self::CheckLimits($hits, $ip, $userAgent);
		if ($hits >= $limit && in_array($ip, Context::Settings()->Limits()->ExcludeIps) == false)
		{
			header('HTTP/1.1 503 Service Temporarily Unavailable');
			header('Status: 503 Service Temporarily Unavailable');
			// header('Retry-After: 9000');
			echo 'Service unavailable / traffic';
			Context::EndRequest();
		}
	}

	public static function DayCompleted() : void
	{
		$locks = [];
		try
		{
			$toZip = [];
			$path = Context::Paths()->GetTrafficLocalPath();
			for($n = 1; $n <= 256 / Traffic::C_FACTOR; $n++)
			{
				$set = self::NumberToFile($n);
				$current = $path . '/' . $set . '.txt';
				if (file_exists($current))
				{
					$lock = new TrafficLock($set);
					$lock->LockWrite();
					$locks[] = $lock;
					$toZip[] = $current;
				}
			}

			$file = $path . '/yesterday.zip';
			IO::Delete($file);
			$zip = new Zip($file);
			$zip->AddToZipDeleting($path, $toZip);
		}
		finally
		{
			foreach($locks as $lock)
				$lock->Release();
		}
		self::ClearDefensiveMode();
	}

	private static function SaveIpHit(string $set, string $ip, string $device) : int
	{
		$file = self::ResolveFilename($set);
		$arr = self::ReadIfExists($file);
		$hits = self::IncrementKey($arr, $ip, $device);
		// graba
		IO::WriteIniFile($file, $arr);
		return $hits;
	}

	private static function IncrementKey(array &$arr, string $key, string $deviceSet) : int
	{
		if (isset($arr[$key]) == false)
		{
			$hits = 1;
			$url = '';
			$agent = '';
		}
		else
		{
			self::ParseHit($arr[$key], $hits, $agent, $url, $_);
			$hits++;

			if ($hits == Context::Settings()->Limits()->LogAgentThresholdDaylyHits)
			{
				$agent = Params::SafeServer('HTTP_USER_AGENT', 'null');
				$url = Params::SafeServer('REQUEST_URI', 'null');
			}
		}
		$value = $hits . "\t" . self::Clean($url) . "\t" . self::Clean($agent) . "\t" . $deviceSet;
		$arr[$key] = $value;
		return $hits;
	}

	private static function Clean(string $str) : string
	{
		$str = str_replace('"', "'", $str);
		$str = str_replace("\t", ";", $str);
		return $str;
	}

	private static function ParseHit($value, &$hits, &$agent, &$url, &$device) : void
	{
		$parts = explode("\t", $value);

		$hits = $parts[0] ;
		if(count($parts) > 3)
			$device = $parts[3];

		$agent = '';
		if (count($parts) > 1)
		{
			$agent = $parts[2];
			$url = $parts[1];
		}
	}

	private static function GetDevicePlural(string $device) : string
	{
		if (Str::EndsWith($device, 'r'))
			return $device . 'es';

		return $device . 's';
	}

	private static function GetDevice() : string
	{
		$detect = new \Mobile_Detect();
		if($detect->isTablet())
			return 'Tablet';
		else if($detect->isMobile())
			return 'Celular';

		return 'Computadora';
	}

	private static function IsMobileOrTablet() : bool
	{
		$detect = new \Mobile_Detect();
		return ($detect->isMobile() || $detect->isTablet());
	}

	private static function GetLimit() : int
	{
		// PDG: comentado por performance..
		// $detect = new \Mobile_Detect();
		// if(self::IsMobileOrTablet())
		// 	return self::GetMobileLimit();
		// else
		return self::GetComputerLimit();
	}

	private static function GetComputerLimit() : int
	{
		if (self::IsInDefensiveMode())
			return Context::Settings()->Limits()->DefensiveModeMaximumDaylyHitsPerIP;

		return Context::Settings()->Limits()->MaximumDaylyHitsPerIP;
	}

	private static function GetMobileLimit() : int
	{
		if (self::IsInDefensiveMode())
			return Context::Settings()->Limits()->DefensiveModeMaximumMobileDaylyHitsPerIP;

		return Context::Settings()->Limits()->MaximumMobileDaylyHitsPerIP;
	}

	private static function CheckLimits($hits, string $ip, string $userAgent) : int
	{
		$limit = self::GetLimit();
		if ($hits == $limit)
		{
			$defensiveMode= '';
			if (self::IsInDefensiveMode())
				$defensiveMode = ' en modo defensivo';

			Performance::SendPerformanceWarning('BLOQUEO por IP (' . $ip . ')' . $defensiveMode, $limit . ' hits', $hits . ' hits', $ip, $userAgent);
		}
		if ($hits == Context::Settings()->Limits()->WarningDaylyHitsPerIP)
		{
			Performance::SendPerformanceWarning('tráfico por IP (' . $ip . ')',
				Context::Settings()->Limits()->WarningDaylyHitsPerIP . ' hits', $hits . ' hits', $ip, $userAgent);
		}
		return $limit;
	}

	private static function ReadIfExists(string $file) : array
	{
		if (file_exists($file))
			return IO::ReadIniFile($file);

		return [];
	}

	private static function ResolveFolder() : string
	{
		$ret = Context::Paths()->GetTrafficLocalPath();
		IO::EnsureExists($ret);
		return $ret;
	}

	public static function NumberToFile(int $number) : string
	{
		return 'hits-' . str_pad(strtoupper(dechex($number)), 2, '0', STR_PAD_LEFT);
	}

	public static function ResolveFilename($set) : string
	{
		$path = self::ResolveFolder();
		return $path . '/' . $set . '.txt';
	}

	public static function GetTraffic($getYesterday, &$totalIps, &$totalHits) : array
	{
		$path = Context::Paths()->GetTrafficLocalPath();
		if ($getYesterday)
		{
			$dir = new CompressedDirectory($path, 'yesterday.zip');
			$dir->Expand();
			$folder = $dir->expandedPath;
		}
		else
		{
			$dir = null;
			$folder = $path;
		}
		$threshold = Context::Settings()->Limits()->LogAgentThresholdDaylyHits;
		$totalIps = [];
		$totalHits = 0;

		$ret = [];
		for($n = 1; $n <= 256 / self::C_FACTOR; $n++)
		{
			$set = self::NumberToFile($n);
			$current = $folder . '/' . $set . '.txt';
			if (file_exists($current))
			{
				$lock = new TrafficLock($set);
				$lock->LockRead();
				$data = IO::ReadIniFile($current);
				$lock->Release();

				foreach($data as $key => $value)
				{
					$url = '';
					self::ParseHit($value, $hits, $agent, $url, $device);
					if ($hits >= $threshold)
					{
						$ret[] = [
							'ip' => $key,
							'hits' => $hits,
							'country' => GeoIp::GetCountryName($key),
							'agent' => $agent,
							'isTotal' => false,
							'url' => $url,
							'device' => $device,
						];
					}
					$totalHits += $hits;
					$devicePlural = self::GetDevicePlural($device);
					if(isset($totalIps[$devicePlural]) == false)
						$totalIps[$devicePlural] = 1;
					else
						$totalIps[$devicePlural] += 1;
				}
			}
		}

		if ($dir !== null)
			$dir->Release();

		Arr::SortByKeyDesc($ret, 'hits');

		$ret[] = Str::BuildTotalsRow($ret, 'ip', ['hits']);
		$ret[count($ret)-1]['ip'] = 'Total (' . (count($ret) - 1) . ')';

		return $ret;
	}

	public static function GoDefensiveMode() : void
	{
		$file = self::ResolveDefensiveFile();
		IO::WriteAllText($file, '1');
	}

	public static function ClearDefensiveMode() : void
	{
		$file = self::ResolveDefensiveFile();
		IO::Delete($file);
	}

	public static function IsInDefensiveMode() : bool
	{
		return file_exists(self::ResolveDefensiveFile());
	}

	private static function ResolveDefensiveFile() : string
	{
		return Context::Paths()->GetTrafficLocalPath() . '/defensive.txt';
	}

}
