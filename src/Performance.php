<?php

namespace minga\framework;

use minga\framework\locking\PerformanceLock;

class Performance
{
	private static $timeStart = null;
	private static $timeEnd = null;

	private static $timePausedStart = null;
	private static $gotFromCache = null;

	private static $controller = null;
	private static $method = null;
	private static $hitCount = 1;
	private static $lockedMs = 0;
	private static $dbMs = 0;
	private static $dbHitCount = 0;
	private static $lockedClass = '';
	private static $locksByClass = [];
	private static $timeStartLocked = null;
	private static $timeStartDb = null;
	private static $daylyResetChecked = false;

	public static $warnToday = null;
	public static $warnYesterday = null;
	public static $pauseEllapsedSecs = 0;

	public static function CacheMissed()
	{
		self::$gotFromCache = false;
	}

	public static function IsCacheMissed()
	{
		return self::$gotFromCache === false;
	}

	public static function Begin()
	{
		self::$timeStart = microtime(true);
	}

	public static function BeginPause()
	{
		self::$timePausedStart = microtime(true);
		Profiling::BeginTimer('Performance::Pause');
	}

	public static function EndPause()
	{
		Profiling::EndTimer();
		$ellapsed = microtime(true) - self::$timePausedStart;
		self::$pauseEllapsedSecs += $ellapsed;
	}

	public static function End()
	{
		self::$timeEnd = microtime(true);
		try
		{
			if (Str::StartsWith(self::$controller, '_profiler') == false)
				self::Save();
		}
		catch(\Exception $e)
		{
			Log::HandleSilentException($e);
		}
		self::$hitCount = 0;

		self::CheckMemoryPeaks();
	}
	private static function CheckMemoryPeaks()
	{
		if (Context::Settings()->Log()->LogMemoryPeaks == false)
			return;
		$usedMB = round(memory_get_peak_usage() / 1024 / 1024, 1);
		if ($usedMB < Context::Settings()->Log()->MemoryPeakMB)
			return;

		$file = Context::Paths()->GetMemoryPeakPath() . '/' . Date::GetLogMonthFolder() . '.txt';
		$line = Date::FormattedArNow() . "\t" . $usedMB . "\t" . Str::UrlencodeFriendly(Context::LoggedUser())
			. "\t" . Request::GetRequestURI();
		try
		{
			if (file_exists($file) == false)
			{
				$header = "Date\tMemory_MB\tUser\tUri";
				IO::AppendLine($file, $header);
			}
			IO::AppendLine($file, $line);
		}
		catch (\Exception $e) {
		}
	}

	public static function BeginLockedWait($class)
	{
		self::$lockedClass = $class;
		self::$timeStartLocked = microtime(true);
	}

	public static function BeginDbWait()
	{
		self::$timeStartDb = microtime(true);
	}

	public static function EndDbWait()
	{
		if (self::$timeStartDb == null)
			return;

		$ellapsedSeconds = microtime(true) - self::$timeStartDb;
		$ellapsedMilliseconds = round($ellapsedSeconds * 1000);

		self::$dbMs += $ellapsedMilliseconds;
		self::$dbHitCount++;

		self::$timeStartDb = null;
	}

	public static function EndLockedWait($hadToWait)
	{
		if (self::$timeStartLocked == null)
			return;

		$ellapsedSeconds = microtime(true) - self::$timeStartLocked;
		$ellapsedMilliseconds = round($ellapsedSeconds * 1000);

		self::$lockedMs += $ellapsedMilliseconds;
		if (array_key_exists(self::$lockedClass, self::$locksByClass))
		{
			$current = self::$locksByClass[self::$lockedClass];
			self::$locksByClass[self::$lockedClass] = [$current[0] + 1, $current[1] + $ellapsedMilliseconds, $current[2] + ($hadToWait ? 1 : 0)];
		}
		else
			self::$locksByClass[self::$lockedClass] = [1, $ellapsedMilliseconds, $hadToWait ? 1 : 0];

		self::$timeStartLocked = null;
	}

	public static function ResolveControllerFromUri()
	{
		// Resuelve el methodName default de performance
		$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
		if (Str::StartsWith($uri, '/'))
			$uri = substr($uri, 1);
		if (Str::EndsWith($uri, '/'))
			$uri = substr($uri, 0, strlen($uri) - 1);
		$uri = Str::Replace($uri, '/', '#');
		if (Str::EndsWith($uri, 'Post'))
			self::SetController(substr($uri, 0, strlen($uri) - 4), "post");
		else
			self::SetController($uri, strtolower($_SERVER['REQUEST_METHOD']));
	}

	public static function SetController($controller, $method, $forceSet = false)
	{
		if (self::$controller == null || $forceSet)
		{
			if(Str::Contains($controller, "\\"))
			{
				$parts = explode("\\", $controller);
				self::$controller = end($parts);
			}
			else
				self::$controller = $controller;
			self::$method = $method;
		}
	}

	public static function SetMethod($method)
	{
		self::$method = $method;
	}

	public static function AddControllerSuffix($suffix)
	{
		if (self::$controller == null)
			self::$controller = '';

		self::$controller .= $suffix;
	}

	private static function Save()
	{
		if (self::$timeStart == null)
			return;
		PerformanceLock::BeginWrite();

		$ellapsedSeconds = microtime(true) - self::$timeStart - self::$pauseEllapsedSecs;
		$ellapsedMilliseconds = round($ellapsedSeconds * 1000);

		// hace válidas las múltiples llamadas a end
		self::$timeStart = self::$timeEnd;
		self::$timeEnd = null;
		self::$pauseEllapsedSecs = 0;
		// completa controllers no informados
		if (self::$controller == null)
		{
			self::$controller = 'na';
			self::$method = 'na';
		}
		// graba mensual
		self::SaveControllerUsage($ellapsedMilliseconds);
		self::SaveUserUsage($ellapsedMilliseconds);
		self::SaveLocks();
		// graba diario
		self::CheckDaylyReset();
		self::SaveControllerUsage($ellapsedMilliseconds, 'dayly');
		self::SaveUserUsage($ellapsedMilliseconds, 'dayly');
		$limitArgs = self::SaveDaylyUsage($ellapsedMilliseconds);
		self::SaveDaylyLocks();
		// listo
		PerformanceLock::EndWrite();

		// Chequea límites
		self::CheckLimits($limitArgs['days'], $limitArgs['key'], $limitArgs['prevHits'], 
												$limitArgs['prevDuration'], $limitArgs['prevLock'], 
												$ellapsedMilliseconds);
	}

	public static function IsNewDay()
	{
		self::CheckDaylyReset();
		return (self::$warnToday != null);
	}

	private static function SaveUserUsage($ellapsedMilliseconds, $month = '')
	{
		if (Context::Settings()->Performance()->PerformancePerUser == false)
			return;

		$file = self::ResolveUserFilename($month);

		if (Str::StartsWith(self::$controller, 'services#backoffice#'))
			$keyMs = 'admin';
		else if (self::$controller === 'admin' || Str::StartsWith(self::$controller, 'admin#'))
			$keyMs = 'admin';
		else
			$keyMs = 'público';

		$vals = self::ReadIfExists($file);
		self::IncrementKey($vals, $keyMs, $ellapsedMilliseconds, self::$hitCount, self::$lockedMs, self::$dbMs, self::$dbHitCount);
		// graba
		IO::WriteIniFile($file, $vals);
	}

	private static function SaveControllerUsage($ellapsedMilliseconds, $month = '')
	{
		$file = self::ResolveFilename($month);
		$keyMs = self::$method;

		$vals = self::ReadIfExists($file);
		self::IncrementKey($vals, $keyMs, $ellapsedMilliseconds, self::$hitCount, self::$lockedMs, self::$dbMs, self::$dbHitCount);
		// graba
		IO::WriteIniFile($file, $vals);
	}

	private static function CheckDaylyReset()
	{
		if(self::$daylyResetChecked)
			return;

		$folder = self::ResolveFolder('dayly');
		$path = $folder . '/today.txt';
		$today = Date::Today();
		if (file_exists($path))
			$todayFolder = IO::ReadAllText($path);
		else
			$todayFolder = '';
		if ($today != $todayFolder)
		{
			// está en un cambio de día
			self::$warnToday = $today;
			self::$warnYesterday = $todayFolder;
		}
		self::$daylyResetChecked = true;

		if (self::IsNewDay())
		{
			// Llama a quienes precisan saber que el día cambió dentro del framework
			self::DayCompleted(self::$warnToday);
			Traffic::DayCompleted();
		}
	}

	private static function DayCompleted($newDay)
	{
		$folder = self::ResolveFolder('dayly');
		$path = $folder . '/today.txt';
		$folderYesterday = self::ResolveFolder('yesterday');
		if (file_exists($folderYesterday))
			IO::RemoveDirectory($folderYesterday);
		IO::Move($folder, $folderYesterday);
		IO::CreateDirectory($folder);
		IO::WriteAllText($path, $newDay);
	}

	private static function ReadDaysValues()
	{
		$daylyProcessor = self::ResolveFilenameDayly();
		return self::ReadIfExists($daylyProcessor);
	}

	public static function ReadTodayExtraValues($key)
	{
		$extras = Context::ExtraHitsLabels();
		$index = Arr::indexOf($extras, $key);
		if ($index === -1)
			return null;
		$days = self::ReadDaysValues();
		$key = Date::GetLogDayFolder();
		self::ReadCurrentKeyValues($days, $key, $prevHits, $prevDuration, $prevLock);
		if (isset($days[$key]) == false)
			return null;
		self::ParseHit($days[$key], $_, $_, $_, $_, $_, $_, $_, $extraHits);
		return $extraHits[$index];
	}

	private static function SaveDaylyUsage($ellapsedMilliseconds)
	{
		$days = self::ReadDaysValues();
		$key = Date::GetLogDayFolder();

		self::ReadCurrentKeyValues($days, $key, $prevHits, $prevDuration, $prevLock);

		$extraHits = Context::ExtraHits();

		self::IncrementLargeKey($days, $key, $ellapsedMilliseconds, self::$hitCount, self::$lockedMs,
			Request::IsGoogle(), Mail::$MailsSent, self::$dbMs, self::$dbHitCount, $extraHits);

		$daylyProcessor = self::ResolveFilenameDayly();
		IO::WriteIniFile($daylyProcessor, $days);

		return ['days' => $days, 'key' => $key, 'prevHits' => $prevHits, 'prevDuration' => $prevDuration,
																 'prevLock' => $prevLock ];
	}

	public static function SaveDaylyLocks()
	{
		self::SaveLocks('dayly');
	}

	public static function SaveLocks($month = '')
	{
		if (sizeof(self::$locksByClass) > 0)
		{
			if ($month == '')
				$month = Date::GetLogMonthFolder();
			$path = self::ResolveFilenameLocks($month);
			$current = self::ReadIfExists($path);

			foreach(self::$locksByClass as $key => $value)
				self::IncrementLockKey($current, $key, $value[2], $value[0], $value[1]);

			IO::WriteIniFile($path, $current);
		}
	}

	private static function CheckLimits($days, $key, $prevHits, $prevDuration, $prevLocked, $ellapsedMilliseconds)
	{
		self::ReadCurrentKeyValues($days, $key, $hits, $duration, $locked);

		$maxMs = Context::Settings()->Limits()->WarningDaylyExecuteMinutes * 60 * 1000;
		$maxHits = Context::Settings()->Limits()->WarningDaylyHits;
		$maxLockMs = Context::Settings()->Limits()->WarningDaylyLockMinutes * 60 * 1000;
		$maxRequestSeconds = Context::Settings()->Limits()->WarningRequestSeconds;

		if ($prevHits < $maxHits && $hits >= $maxHits )
			self::SendPerformanceWarning('hits', $maxHits . ' hits', $hits . ' hits');
		if ($prevDuration < $maxMs && $duration >= $maxMs)
			self::SendPerformanceWarning('minutos de CPU', self::Format($maxMs, 1000 * 60, 'minutos'), self::Format($duration, 1000 * 60, 'minutos'));
		if ($prevLocked < $maxLockMs && $locked >= $maxLockMs)
			self::SendPerformanceWarning('tiempo de locking', self::Format($maxLockMs, 1000 * 60, 'minutos'), self::Format($locked, 1000 * 60, 'minutos'));

		if ($ellapsedMilliseconds >= $maxRequestSeconds * 1000)
			Log::HandleSilentException(new PublicException('El pedido ha excedido los ' . $maxRequestSeconds . ' segundos de ejecución. Tiempo transcurrido: ' . $ellapsedMilliseconds . ' ms.'));

		// Se fija si tiene que pasar a 'defensive Mode'
		$defensiveThreshold = Context::Settings()->Limits()->DefensiveModeThresholdDaylyHits;
		if ($prevHits < $defensiveThreshold && $hits >= $defensiveThreshold)
		{
			Traffic::GoDefensiveMode();
			self::SendPerformanceWarning('activación de modo defensivo', $defensiveThreshold . ' hits', $hits . ' hits');
		}
	}

	public static function SendPerformanceWarning(string $metric, string $limit, string $value, string $ip = '', string $userAgent = '')
	{
		if (empty(Context::Settings()->Mail()->NotifyAddress))
			return;
		// Manda email....
		$server = Str::ToUpper(Context::Settings()->Servers()->Current()->name);

		$mail = new Mail();
		$mail->to = Context::Settings()->Mail()->NotifyAddress;
		$mail->subject = 'ALERTA ADMINISTRATIVA de ' . Context::Settings()->applicationName . ' ' . $server . ' (' . $metric . ' > ' . $limit . ')';
		$vals = [
			'metric' => $metric,
			'limit' => $limit,
			'value' => $value,
			'ip' => $ip,
			'userAgent' => $userAgent,
		];
		$mail->message = Context::Calls()->RenderMessage('performanceWarning.html.twig', $vals);
		$mail->Send(false, true);
	}

	private static function Format($n, $divider, $unit) : string
	{
		return intval($n / $divider) . ' ' . $unit;
	}

	private static function ReadIfExists($file)
	{
		if (file_exists($file))
			return IO::ReadIniFile($file);

		return [];
	}

	private static function ReadCurrentKeyValues($arr, $key, &$prevHits, &$prevDuration, &$locked, &$dbMs = 0, &$dbHits = 0)
	{
		if (array_key_exists($key, $arr) == false)
		{
			$prevHits = 0;
			$prevDuration = 0;
			$locked = 0;
			$dbMs = 0;
			$dbHits = 0;
		}
		else
			self::ParseHit($arr[$key], $prevHits, $prevDuration, $locked, $dbMs, $dbHits);
	}

	private static function IncrementKey(&$arr, $key, $value, $newHits, $newLocked, $newDbMs, $newDb_hitCount)
	{
		if (array_key_exists($key, $arr) == false)
		{
			$hits = $newHits;
			$duration = $value;
			$locked = $newLocked;
			$dbMs = $newDbMs;
			$dbHitCount = $newDb_hitCount;
		}
		else
		{
			self::ParseHit($arr[$key], $hits, $duration, $locked, $dbMs, $dbHitCount);

			$hits += $newHits;
			$duration += $value;
			$locked += $newLocked;
			$dbMs += $newDbMs;
			$dbHitCount += $newDb_hitCount;
		}
		$arr[$key] = $hits . ';' . $duration . ';' . $locked. ';' . $dbMs . ';' . $dbHitCount;
	}

	private static function IncrementLockKey(&$arr, $key, $value, $newHits, $newLocked)
	{
		if (array_key_exists($key, $arr) == false)
		{
			$hits = $newHits;
			$duration = $value;
			$locked = $newLocked;
		}
		else
		{
			self::ParseHit($arr[$key], $hits, $duration, $locked);

			$hits += $newHits;
			$duration += $value;
			$locked += $newLocked;
		}
		$arr[$key] = $hits . ';' . $duration . ';' . $locked;
	}

	private static function IncrementLargeKey(&$arr, $key, $value, $newHits, $newLocked, $isGoogleHit, $mailCount, $newDbMs, $newDbHits, $newExtraHits = [])	{
		if (array_key_exists($key, $arr) == false)
		{
			$hits = $newHits;
			$duration = $value;
			$locked = $newLocked;
			$google = ($isGoogleHit ? 1 : 0);
			$mails = $mailCount;
			$dbMs = $newDbMs;
			$dbHits = $newDbHits;
		}
		else
		{
			self::ParseHit($arr[$key], $hits, $duration, $locked, $google, $mails, $dbMs, $dbHits, $extraHits);
			$hits += $newHits;
			$duration += $value;
			$locked += $newLocked;
			$google += ($isGoogleHit ? 1 : 0);
			$mails += $mailCount;
			$dbMs += $newDbMs;
			$dbHits += $newDbHits;
			for($n = 0; $n < sizeof($newExtraHits); $n++)
			{
				if($n < sizeof($extraHits) && $extraHits[$n])
					$newExtraHits[$n] += $extraHits[$n];
			}
		}
		$arr[$key] = $hits . ';' . $duration . ';' . $locked . ';' . $google . ';' . $mails.
';' . $dbMs . ';' . $dbHits . ';' . implode(',', $newExtraHits);
	}

	private static function ParseHit($value, &$hits, &$duration, &$locked,
		&$p4 = null, &$p5 = null, &$p6 = null, &$p7 = null, &$extra = null)
	{
		$parts = explode(';', $value);
		$hits = $parts[0];
		$duration = $parts[1];
		if (sizeof($parts) > 2)
			$locked = $parts[2];
		else
			$locked = 0;
		if (sizeof($parts) > 3)
		{
			$p4 = (int)$parts[3];
			$p5 = (int)$parts[4];
		}
		else
		{
			$p4 = 0;
			$p5 = 0;
		}
		if (sizeof($parts) > 5)
		{
			$p6 = (int)$parts[5];
			$p7 = (int)$parts[6];
		}
		else
		{
			$p6 = 0;
			$p7 = 0;
		}
		if (sizeof($parts) > 7)
		{
			$extra = explode(',', $parts[7]);
		}
		else
		{
			$extra = [];
		}
	}

	private static function ResolveFolder($month = '')
	{
		$path = Context::Paths()->GetPerformanceLocalPath();
		if ($month == '')
			$month = Date::GetLogMonthFolder();
		$ret = $path . '/' . $month;
		IO::EnsureExists($ret);
		return $ret;
	}

	public static function ResolveFilename($month = '')
	{
		$path = self::ResolveFolder($month);
		return $path . '/' . self::$controller . '.txt';
	}
	public static function ResolveUserFilename($month = '')
	{
		$path = self::ResolveFolder($month);
		$user = '@' . Str::UrlencodeFriendly(Context::LoggedUser());
		return $path . '/' . $user . '.txt';
	}

	public static function ResolveFilenameDayly($month = '')
	{
		$path = self::ResolveFolder($month);
		return $path . '/processor.txt';
	}

	public static function ResolveFilenameLocks($month = '')
	{
		$path = self::ResolveFolder($month);
		return $path . '/locks.txt';
	}

	public static function GetDaylyTable($month)
	{
		$lock = new PerformanceLock();
		$lock->LockRead();

		$path = self::ResolveFilenameDayly($month);
		$days = self::ReadIfExists($path);

		$lock->Release();

		$headerRow = [];
		$dataHitRow = [];
		$dataDbHitRow = [];
		$dataMsRow = [];
		$dataLockedRow = [];
		$dataDbMsRow = [];
		$dataAvgRow = [];

		$googleRow = [];
		$mailRow = [];

		$extras = Context::ExtraHitsLabels();
		$extraValues = array_fill(0, sizeof($extras), []);

		foreach($days as $key => $value)
		{
			$headerRow[] = $key;
			self::ParseHit($value, $hits, $duration, $locked, $google, $mails, $dbMs, $dbHits, $extraHits);
			$dataHitRow[] = $hits;

			$googleRow[] = $google;
			$mailRow[] = $mails;

			$dataMsRow[] = round($duration / 1000 / 60, 1);
			$dataAvgRow[] = round($duration / $hits);
			$dataLockedRow[] = round($locked / 1000, 1);
			$dataDbMsRow[] = round($dbMs / 1000 / 60, 1);
			$dataDbHitRow[] = $dbHits;

			for($n = 0; $n < sizeof($extraValues); $n++)
			{
				if ($n < sizeof($extraHits))
					$extraValues[$n][] = $extraHits[$n];
				else
					$extraValues[$n][] = '-';
			}
		}

		$ret = [
			'Día' => $headerRow,
			'Hits' => $dataHitRow,
			'Promedio (ms.)' => $dataAvgRow,
			'Duración (min.)' => $dataMsRow,
			'Base de datos (min.)' => $dataDbMsRow,
			'Accesos Db' => $dataDbHitRow,
			'Bloqueos (seg.)' => $dataLockedRow,
			'GoogleBot' => $googleRow,
			'Mails' => $mailRow,
		];

		for($n = 0; $n < sizeof($extras); $n++)
		{
			$ret[$extras[$n]] = $extraValues[$n];
		}
		return $ret;
	}

	private static function IsAdmin($controller)
	{
		if ($controller == 'Services') return true;
		$path = Context::Paths()->GetRoot() . '/website/admin/controllers';

		$path2 = Context::Paths()->GetRoot() . '/src/controllers/logs';

		if ($controller === 'logs') $controller = 'logs/activity';
		if ($controller === 'admin') $controller = 'admin/activity';

		$file2 = $path2 . '/c' . Str::Capitalize(Str::Replace($controller, 'logs/', '')) . '.php';
		return file_exists($path . '/' . $controller . '.php') || file_exists($file2);
	}

	public static function GetControllerTable($month, $adminControllers, $getUsers, $methods)
	{
		$lock = new PerformanceLock();
		$lock->LockRead();

		if ($month == '')
			$month = 'dayly';

		$path = self::ResolveFolder($month);
		$rows = [];

		$controllers = [];
		// lee los datos desde disco
		foreach(IO::GetFiles($path, '.txt') as $file)
		{
			if ($file != 'processor.txt' && $file != 'locks.txt')
			{
				$controller = Str::Replace(IO::RemoveExtension($file), '#', '/');
				if ($getUsers === Str::StartsWith($controller, '@'))
				{
					if ($getUsers) $controller = substr($controller, 1);
					$isAdmin = self::IsAdmin($controller);
					if ($isAdmin == $adminControllers)
					{
						$data = self::ReadIfExists($path. '/' . $file);
						$controllers[$controller] = $data;
						foreach($data as $key => $_)
						{
							if (in_array($key, $methods) == false)
								$methods[] = $key;
						}
					}
				}
			}
		}

		$lock->Release();

		// arma fila de encabezados
		$rows = [];
		$headers = [];
		$methodsPlusTotal = array_merge(['Total'], $methods);
		foreach($methodsPlusTotal as $method)
		{
			$headers[] = 'Hits';
			$headers[] = 'Promedio ms';
			$headers[] = 'Db promedio ms (Db hits)';
			$headers[] = 'Share (%)';
		}
		$rows[''] = $methodsPlusTotal;
		$rows['Controllers'] = $headers;

		$totalDuration = self::CalculateTotalDuration($controllers);
		// pone datos
		foreach($controllers as $controller => $values)
		{
			$cells = [0, 0, 0, 0];
			$controllerHits = 0;
			$controllerTime = 0;
			$dbControllerHits = 0;
			$dbControllerMs = 0;
			foreach($methods as $method)
			{
				if (array_key_exists($method, $values))
				{
					self::ParseHit($values[$method], $hits, $duration, $_, $dbMs, $dbHits);
					$controllerHits += $hits;
					$avg = round($duration / $hits);
					$controllerTime += $duration;
					$share = self::FormatShare($duration, $totalDuration);
					$db = round($dbMs / $hits) . ' (' . round($dbHits / $hits, 1) . ')';
					$dbControllerHits += $dbHits;
					$dbControllerMs += $dbMs;
				}
				else
				{
					$hits = '-';
					$avg = '-';
					$db = '-';
					$share = '-';
				}
				$cells[] = $hits;
				$cells[] = $avg;
				$cells[] = $db;
				$cells[] = $share;
			}
			if ($controllerHits > 0)
			{
				$cells[0] = $controllerHits;
				$cells[1] = round($controllerTime / $controllerHits);
				$cells[2] = round($dbControllerMs / $controllerHits) . ' (' . round($dbControllerHits / $controllerHits, 1) . ')';
			}
			else
			{
				$cells[0] = '-';
				$cells[1] = '-';
				$cells[2] = '-';
			}
			$cells[3] = self::FormatShare($controllerTime, $totalDuration);
			$rows[$controller] = $cells;
		}
		ksort($rows);
		return $rows;
	}

	private static function FormatShare($duration, $totalDuration)
	{
		if ($totalDuration == 0)
			$share = '<b>n/d</b>';
		else
		{
			$shareValue = number_format($duration / $totalDuration * 100, 1, ".", "") . '%';
			$share = $shareValue;
			if ($shareValue > 50)
				$share = '<span style="background-color: red">'. $share .'</span>';
			else if ($shareValue > 25)
				$share = '<span style="background-color: yellow">'. $share .'</span>';
			else if ($shareValue > 5)
				$share = '<b>'. $share .'</b>';
		}
		return $share;
	}

	public static function GetLocksTable($month)
	{
		$lock = new PerformanceLock();
		$lock->LockRead();

		if ($month == '') $month = 'dayly';
		$path = self::ResolveFilenameLocks($month);
		$rows = self::ReadIfExists($path);
		$lock->Release();

		ksort($rows);

		$ret = ['Clase' => 'Locks','Promedio (ms)', 'Total (seg.)'];

		foreach($rows as $key => $value)
		{
			self::ParseHit($value, $hits, $waited, $locked);
			$avg = '-';
			if ($waited > 0)
				$avg = round($locked / $waited);

			$cells = [
				$hits . ($waited > 0 ? ' (' . $waited . ')' : ''),
				$avg,
				$locked / 1000,
			];

			$ret[$key . ($waited > 0 ? ' (waited)' : '') ] = $cells;
		}
		return $ret;
	}

	public static function CalculateTotalDuration($controllers)
	{
		$ret = 0;
		foreach($controllers as $values)
		{
			foreach($values as $value)
			{
				self::ParseHit($value, $_, $duration, $_);
				$ret += $duration;
			}
		}
		return $ret;
	}
}
