<?php

namespace minga\framework;

use minga\framework\locking\TrafficLock;

class Traffic
{
	public static function RegisterIP(string $ip, string $userAgent = '', string $url = '', bool $isMegaUser = false) : void
	{
		Profiling::BeginTimer();
		try
		{
			self::Save($ip, $userAgent, $url, $isMegaUser);
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

	/**
	 * a.b.c.d => d
	 */
	private static function GetIpLastPart(string $ip) : int
	{
		$addr = inet_pton($ip);
		if($addr === false)
			throw new ErrorException(Context::Trans('Dirección no válida.'));

		$chars = str_split($addr);
		$last = ord(end($chars));
		return (int)$last;
	}

	private static function Save(string $ip, string $userAgent, string $url, bool $isMegaUser) : void
	{
		if(self::ShouldSave($url, $isMegaUser) == false)
			return;

		$set = self::NumberToFile(self::GetIpLastPart($ip));

		$lock = new TrafficLock(self::GetPreffix() . $set);
		$lock->LockWrite();
		$hits = self::SaveIpHit($set, $ip, $userAgent, $url);
		$lock->Release();

		$limit = self::CheckLimits($hits, $ip, $userAgent);
		if ($hits >= $limit && in_array($ip, Context::Settings()->Limits()->ExcludeIps) == false)
		{
			// header('HTTP/1.1 503 Service Temporarily Unavailable');
			// header('Status: 503 Service Temporarily Unavailable');
			header('HTTP/1.1 429 Too Many Requests');
			header('Retry-After: ' . Date::SecondsToMidnight());
			echo '<html lang="es"><head><meta charset="utf-8"><title>Límite de servidor excedido</title></head>'
				. '<body><h1>Demasiados pedidos al servidor</h1><p>El bloqueo continuará hasta medianoche.</p></body></html>';
			Context::EndRequest();
		}
	}

	private static function ShouldSave(string $url, bool $isMegaUser) : bool
	{
		if(Str::EndsWith($url, '.png'))
			return false;
		if(Str::EndsWith($url, '.jpg'))
			return false;
		if($isMegaUser)
			return false;

		return true;
	}

	public static function DayCompleted() : void
	{
		$locks = [];
		try
		{
			$toZip = [];
			$path = Context::Paths()->GetTrafficLocalPath();
			for($n = 0; $n < 256; $n++)
			{
				$set = self::NumberToFile($n);
				$current = self::ResolveFilename($set, $path);
				if (file_exists($current))
				{
					$lock = new TrafficLock(self::GetPreffix() . $set);
					$lock->LockWrite();
					$locks[] = $lock;
					$toZip[] = $current;
				}
			}
			//}

			$file = $path . '/yesterday.zip';
			IO::Delete($file);
			$zip = new Zip($file);
			$zip->AddToZipDeleting($path, $toZip);
		}
		finally
		{
			foreach($locks as $lock)
				$lock->Release();
			self::ClearDefensiveMode();
		}
	}

	private static function SaveIpHit(string $set, string $ip, string $userAgent, string $url) : int
	{
		$file = self::ResolveFilename($set);
		$arr = IO::ReadIfExists($file);
		$hits = self::IncrementKey($arr, $ip, $userAgent, $url);

		IO::WriteIniFile($file, $arr);
		return $hits;
	}

	private static function IncrementKey(array &$arr, string $key, string $userAgent, string $url) : int
	{
		$data = [
			'hits' => 1,
			'agent' => '',
			'url' => '',
		];
		if (isset($arr[$key]))
		{
			$data = self::ParseHit($arr[$key]);
			$data['hits']++;
			if ($data['hits'] == Context::Settings()->Limits()->LogAgentThresholdDaylyHits)
			{
				$data['agent'] = $userAgent;
				$data['url'] = $url;
			}
		}
		$value = $data['hits'] . "\t" . self::Clean($data['url']) . "\t" . self::Clean($data['agent']);
		$arr[$key] = $value;
		return $data['hits'];
	}

	private static function Clean(string $str) : string
	{
		$str = str_replace('"', "'", $str);
		return str_replace("\t", ";", $str);
	}

	private static function ParseHit(string $value) : array
	{
		$parts = explode("\t", $value);

		$agent = '';
		$url = '';
		if (count($parts) > 1)
		{
			$agent = $parts[2];
			$url = $parts[1];
		}
		return [
			'hits' => (int)$parts[0],
			'agent' => $agent,
			'url' => $url,
		];
	}

	private static function GetLimit() : int
	{
		if (self::IsInDefensiveMode())
			return Context::Settings()->Limits()->DefensiveModeMaximumDaylyHitsPerIP;

		return Context::Settings()->Limits()->MaximumDaylyHitsPerIP;
	}

	private static function CheckLimits(int $hits, string $ip, string $userAgent) : int
	{
		$limit = self::GetLimit();
		if ($hits == $limit)
		{
			$defensiveMode = '';
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

	private static function ResolveFolder() : string
	{
		$ret = Context::Paths()->GetTrafficLocalPath();
		IO::EnsureExists($ret);
		return $ret;
	}

	private static function GetPreffix() : string
	{
		return 'set-';
	}

	private static function NumberToFile(int $number) : string
	{
		return 'hits-' . sprintf('%03d', $number);
	}

	private static function ResolveFilename(string $set, string $path = '') : string
	{
		if($path == '')
			$path = self::ResolveFolder();
		return $path . '/' . self::GetPreffix() . $set . '.txt';
	}

	private static function ReadTrafficFile(string $set, string $file) : array
	{
		$lock = new TrafficLock(self::GetPreffix() . $set);
		$lock->LockRead();
		$data = IO::ReadIniFile($file);
		$lock->Release();
		return $data;
	}

	public static function GetTraffic(bool $getYesterday) : array
	{
		$path = Context::Paths()->GetTrafficLocalPath();
		$dir = null;
		$ret = [
			'ips' => 0,
			'hits' => 0,
			'data' => [],
		];

		if ($getYesterday)
		{
			if(file_exists($path . '/yesterday.zip') == false)
				return $ret;

			$dir = new CompressedDirectory($path, 'yesterday.zip');
			$dir->Expand();
			$path = $dir->expandedPath;
		}

		$threshold = Context::Settings()->Limits()->LogAgentThresholdDaylyHits;
		$results = [];
		$totalIps = [];
		for($n = 0; $n < 256; $n++)
		{
			$set = self::NumberToFile($n);
			$current = self::ResolveFilename($set, $path);
			if (file_exists($current) == false)
				continue;

			$data = self::ReadTrafficFile($set, $current);
			foreach($data as $key => $value)
			{
				$data = self::ParseHit($value);
				if (isset($results[$key]))
				{
					$results[$key]['hits'] += $data['hits'];
					if($results[$key]['agent'] == '')
						$results[$key]['agent'] = $data['agent'];
				}
				else
				{
					$results[$key] = [
						'ip' => $key,
						'hits' => $data['hits'],
						'country' => GeoIp::GetCountryName($key),
						'agent' => $data['agent'],
						'isTotal' => false,
						'url' => $data['url'],
					];
				}
				$ret['hits'] += $data['hits'];
				if(isset($totalIps[$key]) == false)
					$totalIps[$key] = 1;
			}
		}
		if ($dir !== null)
			$dir->Release();

		$ret['ips'] = count($totalIps);
		// filtra
		$tmp = [];
		foreach($results as $key => $value)
		{
			if ($value['hits'] >= $threshold)
				$tmp[] = $value;
		}

		Arr::SortByKeyDesc($tmp, 'hits');
		$ret['data'] = $tmp;
		$ret['data'][] = Aggregate::BuildTotalsRow($tmp, 'ip', ['hits']);
		$last = count($ret['data']) - 1;
		$ret['data'][$last]['ip'] = 'Total (' . $last . ')';

		return $ret;
	}

	public static function GoDefensiveMode() : void
	{
		$file = self::ResolveDefensiveFile();
		IO::WriteAllText($file, '1');
	}

	private static function ClearDefensiveMode() : void
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
