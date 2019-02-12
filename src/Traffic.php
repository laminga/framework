<?php

namespace minga\framework;

use minga\framework\locking\TrafficLock;
use minga\framework\GeoIp;

class Traffic
{
	const C_FACTOR = 4;

	public static function RegisterIP($ip)
	{
		Profiling::BeginTimer();
		try
		{
			self::Save($ip);
		}
		catch(\Exception $e)
		{
			Log::HandleSilentException($e);
		}
		Profiling::EndTimer();
	}

	private static function Save($ipaddr)
	{
		$addr = inet_pton($ipaddr);
		if($addr === false)
			throw new \Exception('Invalid address.');

		$chars = str_split($addr);

		$set = self::NumberToFile(ord($chars[sizeof($chars)-1]) / self::C_FACTOR +1);

		$device = 'n/d'; // comentado por performance self::GetDevice();
		$lock = new TrafficLock($set);

		$lock->LockWrite();
		$hits = self::SaveIpHit($set, $ipaddr, $device);
		$lock->Release();

		$limit = self::CheckLimits($hits);
		if ($hits >= $limit)
		{
			header('HTTP/1.1 503 Service Temporarily Unavailable');
			header('Status: 503 Service Temporarily Unavailable');
			/* header('Retry-After: 9000'); */
			echo 'Service unavailable.';
			Context::EndRequest();
		}
	}

	public static function DayCompleted()
	{
		$path = Context::Paths()->GetTrafficLocalPath();
		$file = $path . "/yesterday.zip";
		IO::Delete($file);
		$zip = new Zip($file);

		for($n = 1; $n <= 256 / Traffic::C_FACTOR; $n++)
		{
			$set = self::NumberToFile($n);
			$current = $path . '/' . $set . '.txt';
			if (file_exists($current))
			{
				$lock = new TrafficLock($set);
				$lock->LockWrite();
				$zip->AddToZip($path, array($current));
				IO::Delete($current);
				$lock->Release();
				}
		}
		self::ClearDefensiveMode();
	}

	private static function SaveIpHit($set, $ipaddr, $device)
	{
		$file = self::ResolveFilename($set);
		$arr = self::ReadIfExists($file);
		$hits = self::IncrementKey($arr, $ipaddr, $device);
		// graba
		IO::WriteIniFile($file, $arr);
		return $hits;
	}

	private static function IncrementKey(&$arr, $key, $deviceSet)
	{
		if (array_key_exists($key, $arr) == false)
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
		$value = $hits . "\t" . $url . "\t" . $agent . "\t" . $deviceSet;
		$arr[$key] = $value;
		return $hits;
	}

	private static function ParseHit($value, &$hits, &$agent, &$url, &$device)
	{
		$parts = explode("\t", $value);

		//Borrar este if a partir del 5/10/2015.
		if(count($parts) == 1)
			$parts = explode('\t', $value);

		$hits = $parts[0] ;
		if(count($parts) > 3)
		{
			$device = $parts[3];
		}

		if (sizeof($parts) > 1)
		{
			$agent = $parts[2];
			$url = $parts[1];
		}
		else
			$agent = '';
	}

	private static function GetDevicePlural($device)
	{
		if (Str::EndsWith($device, 'r'))
			return $device . "es";
		else
			return $device . "s";
	}
	private static function GetDevice()
	{
		$detect = new \Mobile_Detect();
		if($detect->isTablet())
			return 'Tablet';
		else if($detect->isMobile())
			return 'Celular';
		else
			return 'Computadora';
	}

	private static function IsMobileOrTablet()
	{
		$detect = new \Mobile_Detect();
		return ($detect->isMobile() || $detect->isTablet());
	}

	private static function GetLimit()
	{
		/* PDG: comentado por performance..
		/*$detect = new \Mobile_Detect();
		if(self::IsMobileOrTablet())
			return self::GetMobileLimit();
		else*/
		return self::GetComputerLimit();
	}

	private static function GetComputerLimit()
	{
		if (self::IsInDefensiveMode())
			return Context::Settings()->Limits()->DefensiveModeMaximumDaylyHitsPerIP;
		else
			return Context::Settings()->Limits()->MaximumDaylyHitsPerIP;
	}
	private static function GetMobileLimit()
	{
		if (self::IsInDefensiveMode())
			return Context::Settings()->Limits()->DefensiveModeMaximumMobileDaylyHitsPerIP;
		else
			return Context::Settings()->Limits()->MaximumMobileDaylyHitsPerIP;
	}

	private static function CheckLimits($hits)
	{
		$limit = self::GetLimit();
		if ($hits == $limit)
		{
			Performance::SendPerformanceWarning('tráfico por IP', $limit . ' hits', $hits . ' hits');
			if (self::IsInDefensiveMode())
				$defensiveNote = ' en modo defensivo';
			else
				$defensiveNote= '';
			if (self::IsMobileOrTablet())
				$device = ' (' .  self::GetDevice() .')';
			else
				$device = '';

			Log::HandleSilentException(new MessageException('La IP' . $device. ' ha llegado al máximo permitido de ' . $limit . ' hits' . $defensiveNote . '.'));
		}
		if ($hits == Context::Settings()->Limits()->WarningDaylyHitsPerIP)
			Performance::SendPerformanceWarning('tráfico por IP sospechoso', Context::Settings()->Limits()->WarningDaylyHitsPerIP . ' hits', $hits . ' hits');
		return $limit;
	}

	private static function ReadIfExists($file)
	{
		if (file_exists($file))
			return IO::ReadIniFile($file);
		else
			return array();
	}

	private static function ResolveFolder()
	{
		$ret = Context::Paths()->GetTrafficLocalPath();
		IO::EnsureExists($ret);
		return $ret;
	}
	public static function NumberToFile($number)
	{
		return 'hits-' . str_pad(strtoupper(dechex($number)), 2, '0', STR_PAD_LEFT);
	}
	public static function ResolveFilename($set)
	{
		$path = self::ResolveFolder();
		return $path . '/' . $set . '.txt';
	}

	public static function GetTraffic($getYesterday, &$totalIps, &$totalHits)
	{
		$ret = array();
		$path = Context::Paths()->GetTrafficLocalPath();

		if ($getYesterday)
		{
			$dir = new CompressedDirectory($path, "yesterday.zip");
			$dir->Expand();
			$folder = $dir->expandedPath;
		}
		else
		{
			$dir = null;
			$folder = $path;
		}
		$threshold = Context::Settings()->Limits()->LogAgentThresholdDaylyHits;
		$totalIps = array();
		$totalHits = 0;

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
						$item = array();
						$item['ip'] = $key;
						$item['hits'] = $hits;
						$item['country'] = GeoIp::GetCountryName($key);
						$item['agent'] = $agent;
						$item['isTotal'] = false;
						$item['url'] = $url;
						$item['device'] = $device;
						$ret[] = $item;
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

		$ret[] = Str::BuildTotalsRow($ret, 'ip', array('hits'));
		$ret[count($ret)-1]['ip'] = 'Total (' . (sizeof($ret) - 1) .')';

		return $ret;
	}

	public static function GoDefensiveMode()
	{
		$file = self::ResolveDefensiveFile();
		IO::WriteAllText($file, '1');

	}

	public static function ClearDefensiveMode()
	{
		$file = self::ResolveDefensiveFile();
		IO::Delete($file);
	}

	public static function IsInDefensiveMode()
	{
		return file_exists(self::ResolveDefensiveFile());
	}

	private static function ResolveDefensiveFile()
	{
		return Context::Paths()->GetTrafficLocalPath() . '/defensive.txt';
	}

}
