<?php

namespace minga\framework;

use minga\framework\locking\PerformanceDaylyLocksLock;
use minga\framework\locking\PerformanceDaylyUsageLock;
use minga\framework\locking\PerformanceDaylyUserLock;
use minga\framework\locking\PerformanceMonthlyDayLock;
use minga\framework\locking\PerformanceMonthlyLocksLock;
use minga\framework\locking\PerformanceMonthlyTotalEmailsLock;
use minga\framework\locking\PerformanceMonthlyUsageLock;
use minga\framework\locking\PerformanceMonthlyUserLock;

class Performance
{
	private static ?float $timeStart = null;
	private static ?float $timeEnd = null;

	private static ?float $timePausedStart = null;
	private static bool $gotFromCache = true;

	private static ?string $controller = null;
	private static ?string $method = null;
	private static int $hitCount = 1;
	private static int $lockedMs = 0;
	private static int $dbMs = 0;
	private static int $dbHitCount = 0;
	private static string $lockedClass = '';
	private static array $locksByClass = [];
	private static ?float $timeStartLocked = null;
	private static ?float $timeStartDb = null;
	private static bool $daylyResetChecked = false;

	public static ?string $warnToday = null;
	public static ?string $warnYesterday = null;
	public static int $pauseEllapsedSecs = 0;

	public static bool $allowLongRunningRequest = false;

	public static int $mailsSent = 0;

	public static function CacheMissed() : void
	{
		self::$gotFromCache = false;
	}

	public static function IsCacheMissed() : bool
	{
		return self::$gotFromCache == false;
	}

	public static function Begin() : void
	{
		self::$timeStart = microtime(true);
	}

	public static function BeginPause() : void
	{
		self::$timePausedStart = microtime(true);
		Profiling::BeginTimer('Performance::Pause');
	}

	public static function EndPause() : void
	{
		Profiling::EndTimer();
		$ellapsed = microtime(true) - self::$timePausedStart;
		self::$pauseEllapsedSecs += (int)$ellapsed;
	}

	public static function End() : void
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

	private static function CheckMemoryPeaks() : void
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
		catch (\Exception $e)
		{
		}
	}

	public static function BeginLockedWait(string $class) : void
	{
		self::$lockedClass = $class;
		self::$timeStartLocked = microtime(true);
	}

	public static function BeginDbWait() : void
	{
		self::$timeStartDb = microtime(true);
	}

	public static function EndDbWait() : void
	{
		if (self::$timeStartDb == null)
			return;

		$ellapsedSeconds = microtime(true) - self::$timeStartDb;
		$ellapsedMilliseconds = (int)round($ellapsedSeconds * 1000);

		self::$dbMs += $ellapsedMilliseconds;
		self::$dbHitCount++;

		self::$timeStartDb = null;
	}

	public static function EndLockedWait(bool $hadToWait) : void
	{
		if (self::$timeStartLocked == null)
			return;

		$ellapsedSeconds = microtime(true) - self::$timeStartLocked;
		$ellapsedMilliseconds = (int)round($ellapsedSeconds * 1000);

		self::$lockedMs += $ellapsedMilliseconds;
		if (isset(self::$locksByClass[self::$lockedClass]))
		{
			$current = self::$locksByClass[self::$lockedClass];
			self::$locksByClass[self::$lockedClass] = [$current[0] + 1, $current[1] + $ellapsedMilliseconds, $current[2] + ($hadToWait ? 1 : 0)];
		}
		else
			self::$locksByClass[self::$lockedClass] = [1, $ellapsedMilliseconds, $hadToWait ? 1 : 0];

		self::$timeStartLocked = null;
	}

	public static function ResolveControllerFromUri() : void
	{
		// Resuelve el methodName default de performance
		$uri = parse_url(Params::SafeServer("REQUEST_URI"), PHP_URL_PATH);
		if (Str::StartsWith($uri, '/'))
			$uri = substr($uri, 1);
		if (Str::EndsWith($uri, '/'))
			$uri = substr($uri, 0, strlen($uri) - 1);
		$uri = Str::Replace($uri, '/', '#');
		if (Str::EndsWith($uri, 'Post'))
			self::SetController(substr($uri, 0, strlen($uri) - 4), "post");
		else
			self::SetController($uri, strtolower(Params::SafeServer('REQUEST_METHOD')));
	}

	public static function AppendControllerSuffix(string $suffix) : void
	{
		self::$controller .= "#" . $suffix;
	}

	public static function SetController(?string $controller, ?string $method, bool $forceSet = false) : void
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

	public static function SetMethod(string $method) : void
	{
		self::$method = $method;
	}

	public static function AddControllerSuffix(string $suffix) : void
	{
		if (self::$controller == null)
			self::$controller = '';

		self::$controller .= $suffix;
	}

	private static function Save() : void
	{
		if (self::$timeStart == null)
			return;
		$ellapsedSeconds = microtime(true) - self::$timeStart - self::$pauseEllapsedSecs;
		$ellapsedMilliseconds = (int)round($ellapsedSeconds * 1000);

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
		$limitArgs = self::SaveMonthtly($ellapsedMilliseconds);

		// se fija si avisa el cambio de día
		self::CheckDaylyReset();

		// graba diario
		PerformanceDaylyUsageLock::BeginWrite();
		self::SaveControllerUsage($ellapsedMilliseconds, 'dayly');
		PerformanceDaylyUsageLock::EndWrite();

		if (Context::Settings()->Performance()->PerformancePerUser)
		{
			PerformanceDaylyUserLock::BeginWrite();
			self::SaveUserUsage($ellapsedMilliseconds, 'dayly');
			PerformanceDaylyUserLock::EndWrite();
		}

		PerformanceDaylyLocksLock::BeginWrite();
		self::SaveDaylyLocks();
		PerformanceDaylyLocksLock::EndWrite();

		// Chequea límites
		self::CheckLimits($limitArgs['days'], $limitArgs['key'], $limitArgs['prevHits'],
			$limitArgs['prevDuration'], $limitArgs['prevLock'],
			$ellapsedMilliseconds);

		while(self::$mailsSent > 0)
		{
			PerformanceMonthlyTotalEmailsLock::BeginWrite();
			$totalSent = self::SaveTotalEmails(self::$mailsSent);
			PerformanceMonthlyTotalEmailsLock::EndWrite();
			$sent = self::$mailsSent;
			self::$mailsSent = 0;
			self::CheckMailLimits($sent, $totalSent);
		}
	}

	private static function SaveMonthtly(int $ellapsedMilliseconds) : array
	{
		PerformanceMonthlyUsageLock::BeginWrite();
		self::SaveControllerUsage($ellapsedMilliseconds);
		PerformanceMonthlyUsageLock::EndWrite();
		if (Context::Settings()->Performance()->PerformancePerUser)
		{
			PerformanceMonthlyUserLock::BeginWrite();
			self::SaveUserUsage($ellapsedMilliseconds);
			PerformanceMonthlyUserLock::EndWrite();
		}
		PerformanceMonthlyLocksLock::BeginWrite();
		self::SaveLocks();
		PerformanceMonthlyLocksLock::EndWrite();

		PerformanceMonthlyDayLock::BeginWrite();
		$limitArgs = self::SaveDaylyUsage($ellapsedMilliseconds);
		PerformanceMonthlyDayLock::EndWrite();
		return $limitArgs;
	}

	public static function IsNewDay() : bool
	{
		self::CheckDaylyReset();
		return self::$warnToday != null;
	}

	private static function SaveUserUsage(int $ellapsedMilliseconds, string $month = '') : void
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

	private static function SaveControllerUsage(int $ellapsedMilliseconds, string $month = '') : void
	{
		$file = self::ResolveFilename($month);
		$keyMs = self::$method;

		$vals = self::ReadIfExists($file);

		self::IncrementKey($vals, $keyMs, $ellapsedMilliseconds, self::$hitCount, self::$lockedMs, self::$dbMs, self::$dbHitCount);
		// graba
		IO::WriteIniFile($file, $vals);
	}

	private static function CheckDaylyReset() : void
	{
		if(self::$daylyResetChecked)
			return;

		PerformanceDaylyUsageLock::BeginWrite();
		if (Context::Settings()->Performance()->PerformancePerUser)
			PerformanceDaylyUserLock::BeginWrite();
		PerformanceDaylyLocksLock::BeginWrite();

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

		PerformanceDaylyUsageLock::EndWrite();
		if (Context::Settings()->Performance()->PerformancePerUser)
			PerformanceDaylyUserLock::EndWrite();
		PerformanceDaylyLocksLock::EndWrite();
	}

	private static function DayCompleted(?string $newDay) : void
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

	private static function ReadDaysValues() : array
	{
		$daylyProcessor = self::ResolveFilenameDayly();
		return self::ReadIfExists($daylyProcessor);
	}

	public static function ReadTodayExtraValues(string $key) : ?int
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

	private static function SaveDaylyUsage(int $ellapsedMilliseconds) : array
	{
		$days = self::ReadDaysValues();
		$key = Date::GetLogDayFolder();

		self::ReadCurrentKeyValues($days, $key, $prevHits, $prevDuration, $prevLock);

		$extraHits = Context::ExtraHits();

		self::IncrementLargeKey($days, $key, $ellapsedMilliseconds, self::$hitCount, self::$lockedMs,
			Request::IsGoogle(), self::$mailsSent, self::$dbMs, self::$dbHitCount, $extraHits);

		$daylyProcessor = self::ResolveFilenameDayly();
		IO::WriteIniFile($daylyProcessor, $days);

		return [
			'days' => $days,
			'key' => $key,
			'prevHits' => $prevHits,
			'prevDuration' => $prevDuration,
			'prevLock' => $prevLock,
		];
	}

	public static function SaveTotalEmails(int $cant) : int
	{
		$file = self::ResolveFilenameTotalEmails();
		$total = $cant;
		if(file_exists($file))
			$total += (int)file_get_contents($file);

		IO::WriteAllText($file, $total);
		return $total;
	}

	public static function SaveDaylyLocks() : void
	{
		self::SaveLocks('dayly');
	}

	public static function SaveLocks(string $month = '') : void
	{
		if (count(self::$locksByClass) > 0)
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

	private static function CheckMailLimits(int $sent, int $totalSent) : void
	{
		$warning = Context::Settings()->Limits()->WarningMonthlyEmails;
		$limit = Context::Settings()->Limits()->LimitMonthlyEmails;
		$before = $totalSent - $sent;
		if ($warning > $before && $warning <= $totalSent)
			Performance::SendPerformanceWarning('cantidad de emails enviados', $warning . ' emails', $totalSent . ' emails');

		if ($limit > $before && $limit <= $totalSent)
			Performance::SendPerformanceWarning('límite de emails enviados AGOTADO', $limit . ' emails', $totalSent . ' emails');
	}

	private static function CheckLimits(array $days, string $key, int $prevHits, int $prevDuration, int $prevLocked, int $ellapsedMilliseconds) : void
	{
		self::ReadCurrentKeyValues($days, $key, $hits, $duration, $locked);

		$maxMs = Context::Settings()->Limits()->WarningDaylyExecuteMinutes * 60 * 1000;
		$maxHits = Context::Settings()->Limits()->WarningDaylyHits;
		$maxLockMs = Context::Settings()->Limits()->WarningDaylyLockMinutes * 60 * 1000;
		$maxRequestSeconds = Context::Settings()->Limits()->WarningRequestSeconds;

		if ($prevHits < $maxHits && $hits >= $maxHits)
			self::SendPerformanceWarning('hits', $maxHits . ' hits', $hits . ' hits');
		if ($prevDuration < $maxMs && $duration >= $maxMs)
			self::SendPerformanceWarning('minutos de CPU', self::Format($maxMs, 1000 * 60, 'minutos'), self::Format($duration, 1000 * 60, 'minutos'));
		if ($prevLocked < $maxLockMs && $locked >= $maxLockMs)
			self::SendPerformanceWarning('tiempo de locking', self::Format($maxLockMs, 1000 * 60, 'minutos'), self::Format($locked, 1000 * 60, 'minutos'));

		if ($ellapsedMilliseconds >= $maxRequestSeconds * 1000 && self::$allowLongRunningRequest == false)
			Log::HandleSilentException(new PublicException('El pedido ha excedido los ' . $maxRequestSeconds . ' segundos de ejecución. Tiempo transcurrido: ' . $ellapsedMilliseconds . ' ms.'));

		// Se fija si tiene que pasar a 'defensive Mode'
		$defensiveThreshold = Context::Settings()->Limits()->DefensiveModeThresholdDaylyHits;
		if ($prevHits < $defensiveThreshold && $hits >= $defensiveThreshold)
		{
			Traffic::GoDefensiveMode();
			self::SendPerformanceWarning('activación de modo defensivo', $defensiveThreshold . ' hits', $hits . ' hits');
		}
	}

	public static function SendPerformanceWarning(string $metric, string $limit, string $value, string $ip = '', string $userAgent = '') : void
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

	private static function Format(int $n, int $divider, string $unit) : string
	{
		return (int)($n / $divider) . ' ' . $unit;
	}

	private static function ReadIfExists(string $file) : array
	{
		if (file_exists($file))
			return IO::ReadIniFile($file);

		return [];
	}

	private static function ReadCurrentKeyValues(array $arr, string $key, ?int &$prevHits, ?int &$prevDuration, ?int &$locked, ?int &$dbMs = 0, ?int &$dbHits = 0) : void
	{
		if (isset($arr[$key]) == false)
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

	private static function IncrementKey(array &$arr, $key, int $value, int $newHits, int $newLocked, int $newDbMs, int $newDbHitCount) : void
	{
		if (isset($arr[$key]) == false)
		{
			$hits = $newHits;
			$duration = $value;
			$locked = $newLocked;
			$dbMs = $newDbMs;
			$dbHitCount = $newDbHitCount;
		}
		else
		{
			self::ParseHit($arr[$key], $hits, $duration, $locked, $dbMs, $dbHitCount);

			$hits += $newHits;
			$duration += $value;
			$locked += $newLocked;
			$dbMs += $newDbMs;
			$dbHitCount += $newDbHitCount;
		}
		$arr[$key] = $hits . ';' . $duration . ';' . $locked . ';' . $dbMs . ';' . $dbHitCount;
	}

	private static function IncrementLockKey(array &$arr, string $key, int $value, int $newHits, int $newLocked) : void
	{
		if (isset($arr[$key]) == false)
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

	private static function IncrementLargeKey(array &$arr, $key, int $value, int $newHits, int $newLocked,
		bool $isGoogleHit, int $mailCount, int $newDbMs, int $newDbHits, array $newExtraHits = []) : void
	{
		if (isset($arr[$key]) == false)
		{
			$hits = $newHits;
			$duration = $value;
			$locked = $newLocked;
			$google = (int)$isGoogleHit;
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
			$google += (int)$isGoogleHit;
			$mails += $mailCount;
			$dbMs += $newDbMs;
			$dbHits += $newDbHits;
			for($n = 0; $n < count($newExtraHits); $n++)
			{
				if($n < count($extraHits) && $extraHits[$n])
					$newExtraHits[$n] += $extraHits[$n];
			}
		}
		$arr[$key] = $hits . ';' . $duration . ';' . $locked . ';' . $google . ';' . $mails
			. ';' . $dbMs . ';' . $dbHits . ';' . implode(',', $newExtraHits);
	}

	private static function ParseHit(string $value, ?string &$hits, ?string &$duration, ?string &$locked,
		?int &$p4 = null, ?int &$p5 = null, ?int &$p6 = null, ?int &$p7 = null, ?array &$extra = null) : void
	{
		$parts = explode(';', $value);
		$hits = $parts[0];
		$duration = $parts[1];
		if (count($parts) > 2)
			$locked = $parts[2];
		else
			$locked = "0";
		if (count($parts) > 3)
		{
			$p4 = (int)$parts[3];
			$p5 = (int)$parts[4];
		}
		else
		{
			$p4 = 0;
			$p5 = 0;
		}
		if (count($parts) > 5)
		{
			$p6 = (int)$parts[5];
			$p7 = (int)$parts[6];
		}
		else
		{
			$p6 = 0;
			$p7 = 0;
		}
		if (count($parts) > 7)
			$extra = explode(',', $parts[7]);
		else
			$extra = [];
	}

	private static function ResolveFolder(string $month = '') : string
	{
		$path = Context::Paths()->GetPerformanceLocalPath();
		if ($month == '')
			$month = Date::GetLogMonthFolder();
		$ret = $path . '/' . $month;
		IO::EnsureExists($ret);
		return $ret;
	}

	public static function ResolveFilename(string $month = '') : string
	{
		$path = self::ResolveFolder($month);
		return $path . '/' . self::$controller . '.txt';
	}

	public static function ResolveUserFilename(string $month = '') : string
	{
		$path = self::ResolveFolder($month);
		$user = '@' . Str::UrlencodeFriendly(Context::LoggedUser());
		return $path . '/' . $user . '.txt';
	}

	public static function ResolveFilenameTotalEmails(string $month = '') : string
	{
		$path = self::ResolveFolder($month);
		return $path . '/totalEmails.txt';
	}

	public static function ResolveFilenameDayly(string $month = '') : string
	{
		$path = self::ResolveFolder($month);
		return $path . '/processor.txt';
	}

	public static function ResolveFilenameLocks(string $month = '') : string
	{
		$path = self::ResolveFolder($month);
		return $path . '/locks.txt';
	}

	public static function GetDaylyTable(string $month, bool $appendTotals = false) : array
	{
		$lock = new PerformanceMonthlyDayLock();
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
		$extraValues = array_fill(0, count($extras), []);

		// Para columna de totales
		$totalsDataHitRow = 0;
		$totalsGoogleRow = 0;
		$totalsMailRow = 0;
		$totalsDataMsRow = 0;
		$totalsAvgRow = 0;
		$totalsDataLockedRow = 0;
		$totalsDataDbMsRow = 0;
		$totalsDataDbHitRow = 0;
		$totalsExtraValues = array_fill(0, count($extraValues), 0);

		foreach($days as $key => $value)
		{
			$headerRow[] = $key;
			self::ParseHit($value, $hits, $duration, $locked, $google, $mails, $dbMs, $dbHits, $extraHits);
			$dataHitRow[] = $hits;
			$totalsDataHitRow += $hits;

			$googleRow[] = $google;
			$totalsGoogleRow += $google;
			$mailRow[] = $mails;
			$totalsMailRow += $mails;

			$dataMsRow[] = round($duration / 1000 / 60, 1);
			$totalsDataMsRow += $duration;
			$dataAvgRow[] = round($duration / $hits);
			$totalsAvgRow += ($duration / $hits);
			$dataLockedRow[] = round($locked / 1000, 1);
			$totalsDataLockedRow += $locked;
			$dataDbMsRow[] = round($dbMs / 1000 / 60, 1);
			$totalsDataDbMsRow += $dbMs;
			$dataDbHitRow[] = $dbHits;
			$totalsDataDbHitRow += $dbHits;

			for($n = 0; $n < count($extraValues); $n++)
			{
				if ($n < count($extraHits))
				{
					$extraValues[$n][] = $extraHits[$n];
					if ($extraHits[$n])
						$totalsExtraValues[$n] += $extraHits[$n];
				}
				else
					$extraValues[$n][] = '-';
			}
		}
		if ($appendTotals)
		{
			// Agrega la columna de promedios diarios
			$headerRow[] = 'Promedio';
			$dataHitRow[] = self::Average($totalsDataHitRow, count($days));
			$googleRow[] = self::Average($totalsGoogleRow, count($days));
			$mailRow[] = self::Average($totalsMailRow, count($days));
			$dataMsRow[] = self::Average($totalsDataMsRow / 1000 / 60, count($days));
			$dataAvgRow[] = '-'; //round($totalsAvgRow / count($days));
			$dataLockedRow[] = self::Average($totalsDataLockedRow / 1000, count($days));
			$dataDbMsRow[] = self::Average($totalsDataDbMsRow / 1000 / 60, count($days));
			$dataDbHitRow[] = self::Average($totalsDataDbHitRow, count($days));
			for($n = 0; $n < count($extraValues); $n++)
				$extraValues[$n][] = self::Average($totalsExtraValues[$n], count($days));

			// Agrega la columna de totales
			$headerRow[] = 'Total';
			$dataHitRow[] = $totalsDataHitRow;
			$googleRow[] = $totalsGoogleRow;
			$mailRow[] = $totalsMailRow;
			$dataMsRow[] = round($totalsDataMsRow / 1000 / 60, 1);
			$dataAvgRow[] = self::Average($totalsDataMsRow, $totalsDataHitRow);
			$dataLockedRow[] = round($totalsDataLockedRow / 1000, 1);
			$dataDbMsRow[] = round($totalsDataDbMsRow / 1000 / 60, 1);
			$dataDbHitRow[] = $totalsDataDbHitRow;
			for($n = 0; $n < count($extraValues); $n++)
				$extraValues[$n][] = $totalsExtraValues[$n];
		}
		// Arma la matriz
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

		for($n = 0; $n < count($extras); $n++)
			$ret[$extras[$n]] = $extraValues[$n];
		return $ret;
	}

	private static function Average(int $a, int $b) : string
	{
		if ($b == 0)
			return '';

		return '' . round($a / $b);
	}

	public static function GetHistoryTable(array $months) : array
	{
		$ret = null;
		$actualMoths = [];
		for($n = count($months) - 1; $n >= 0; $n--)
			if (Str::StartsWith($months[$n], '2'))
				$actualMoths[] = $months[$n];

		foreach($actualMoths as $month)
		{
			if (Str::StartsWith($month, '2'))
			{
				$monthInfo = self::GetDaylyTable($month, true);
				if ($ret === null)
				{
					$ret = $monthInfo;
					foreach($ret as $key => $value)
						$ret[$key] = [$value[count($value) - 1]];
				}
				else
				{
					foreach($monthInfo as $key => $value)
						$ret[$key][] = $value[count($value) - 1];
				}

			}
		}
		unset($ret['Día']);
		return array_merge(['Mes' => $actualMoths], $ret);
	}

	private static function IsAdmin(string $controller) : bool
	{
		if ($controller == 'Services')
			return true;
		$path = Context::Paths()->GetRoot() . '/website/admin/controllers';

		$path2 = Context::Paths()->GetRoot() . '/src/controllers/logs';

		if ($controller === 'logs') $controller = 'logs/activity';
		if ($controller === 'admin') $controller = 'admin/activity';

		$file2 = $path2 . '/c' . Str::Capitalize(Str::Replace($controller, 'logs/', '')) . '.php';
		return file_exists($path . '/' . $controller . '.php') || file_exists($file2);
	}

	public static function GetControllerTable(string $month, bool $adminControllers, bool $getUsers, array $methods) : array
	{
		if ($month == '')
			$month = 'dayly';
		$lockUser = null;
		if ($month === 'dayly' || $month === 'yesterday')
		{
			$lock = new PerformanceDaylyUsageLock();
			if (Context::Settings()->Performance()->PerformancePerUser)
				$lockUser = new PerformanceDaylyUserLock();
		}
		else
		{
			$lock = new PerformanceMonthlyUsageLock();
			if (Context::Settings()->Performance()->PerformancePerUser)
				$lockUser = new PerformanceMonthlyUserLock();
		}
		$lock->LockRead();
		if (Context::Settings()->Performance()->PerformancePerUser)
			$lockUser->LockRead();

		$path = self::ResolveFolder($month);
		$rows = [];

		$controllers = [];
		// lee los datos desde disco
		foreach(IO::GetFiles($path, '.txt') as $file)
		{
			if ($file != 'processor.txt' && $file != 'locks.txt')
			{
				$controller = Str::Replace(IO::RemoveExtension($file), '#', '/');
				if ($getUsers == Str::StartsWith($controller, '@'))
				{
					if ($getUsers)
						$controller = substr($controller, 1);
					$isAdmin = self::IsAdmin($controller);
					if ($isAdmin == $adminControllers)
					{
						$data = self::ReadIfExists($path . '/' . $file);
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
		if (Context::Settings()->Performance()->PerformancePerUser)
			$lockUser->Release();

		// arma fila de encabezados
		$rows = [];
		$headers = [];
		$methodsPlusTotal = array_merge(['Total'], $methods);
		foreach($methodsPlusTotal as $method)
		{
			if (sizeof($headers) == 0)
			{
				$headers[] = 'Minutes of CPU';
				$headers[] = 'Share CPU (%)';
			}
			$headers[] = 'Hits';
			$headers[] = 'Promedio ms';
			$headers[] = 'Db promedio ms (Db hits)';
			$headers[] = 'Db Share (%)';
		}
		$rows[''] = $methodsPlusTotal;
		$rows['Controllers'] = $headers;
		$totalDuration = self::CalculateTotalDuration($controllers);
		// calcula el total de minutos
		$totalMinutes = 0;

		foreach($controllers as $controller => $values)
		{
			foreach($methods as $method)
			{
				if (isset($values[$method]))
				{
					self::ParseHit($values[$method], $hits, $duration, $_, $dbMs, $dbHits);
					$totalMinutes += $duration / 1000 / 60;
				}
			}
		}
		// genera celdas
		foreach($controllers as $controller => $values)
		{
			$fist = true;
			$cells = [0, 0, 0, 0];
			$controllerHits = 0;
			$controllerTime = 0;
			$dbControllerHits = 0;
			$dbControllerMs = 0;
			foreach($methods as $method)
			{
				$myHits = 0;
				if (isset($values[$method]))
				{
					self::ParseHit($values[$method], $hits, $duration, $_, $dbMs, $dbHits);
					$controllerHits += $hits;
					$myHits = $hits;
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
			if ($fist)
			{
				if ($myHits > 0)
				{
					$minutes = $cells[0] * $cells[1] / 1000 / 60;
					array_unshift($cells, self::FormatShare($minutes, $totalMinutes));
					array_unshift($cells, round($minutes * 10) / 10);
				}
				else
				{
					array_unshift($cells, '-');
					array_unshift($cells, '-');
				}
			}
			if ($controller == '')
				$rows['n/d'] = $cells;
			else
				$rows[$controller] = $cells;

			$fist = false;
		}
		ksort($rows);
		return $rows;
	}

	private static function FormatShare(int $duration, int $totalDuration) : string
	{
		if ($totalDuration == 0)
			$share = '<b>n/d</b>';
		else
		{
			$shareValue = number_format($duration / $totalDuration * 100, 1, ".", "") . '%';
			$share = $shareValue;
			if ($shareValue > 50)
				$share = '<span style="background-color: red">' . $share . '</span>';
			else if ($shareValue > 25)
				$share = '<span style="background-color: yellow">' . $share . '</span>';
			else if ($shareValue > 5)
				$share = '<b>' . $share . '</b>';
		}
		return $share;
	}

	public static function GetLocksTable(string $month) : array
	{
		if ($month == '')
			$month = 'dayly';

		if ($month === 'dayly' || $month === 'yesterday')
			$lock = new PerformanceDaylyLocksLock();
		else
			$lock = new PerformanceMonthlyLocksLock();

		$lock->LockRead();

		$path = self::ResolveFilenameLocks($month);
		$rows = self::ReadIfExists($path);
		$lock->Release();

		ksort($rows);

		$ret = ['Clase' => ['Locks', 'Promedio (ms)', 'Total (seg.)']];

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

			$ret[$key . ($waited > 0 ? ' (waited)' : '')] = $cells;
		}
		return $ret;
	}

	public static function CalculateTotalDuration(array $controllers) : int
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
