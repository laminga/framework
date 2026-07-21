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
	private static int $errorCount = 0;
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

	/**
	 * @deprecated usar PerformanceTable::GetDaylyTable()
	 */
	public static function GetDaylyTable(string $month, bool $appendTotals = false) : array
	{
		return PerformanceTable::GetDaylyTable($month, $appendTotals);
	}

	/**
	 * @deprecated usar PerformanceTable::GetHistoryTable()
	 */
	public static function GetHistoryTable(array $months) : array
	{
		return PerformanceTable::GetHistoryTable($months);
	}

	/**
	 * @deprecated usar PerformanceTable::GetControllerTable()
	 */
	public static function GetControllerTable(string $month, bool $adminControllers, bool $getUsers, array $methods) : array
	{
		return PerformanceTable::GetControllerTable($month, $adminControllers, $getUsers, $methods);
	}

	/**
	 * @deprecated usar PerformanceTable::GetMailsTable()
	 */
	public static function GetMailsTable(string $month) : array
	{
		return PerformanceTable::GetMailsTable($month);
	}

	/**
	 * @deprecated usar PerformanceTable::GetLocksTable()
	 */
	public static function GetLocksTable(string $month) : array
	{
		return PerformanceTable::GetLocksTable($month);
	}

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
		self::$errorCount = 0;

		self::CheckMemoryPeaks();
	}

	public static function GetCurrentErrorCount() : int
	{
		return self::$errorCount;
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

	public static function IncrementErrors() : void
	{
		self::$errorCount++;
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
			if(Str::Contains((string)$controller, "\\"))
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

		$vals = IO::ReadIfExists($file);
		self::IncrementKey($vals, $keyMs, $ellapsedMilliseconds, self::$hitCount, self::$lockedMs, self::$dbMs, self::$dbHitCount);
		// graba
		IO::WriteIniFile($file, $vals);
	}

	private static function SaveControllerUsage(int $ellapsedMilliseconds, string $month = '') : void
	{
		$file = self::ResolveFilename($month);
		$keyMs = self::$method;

		$vals = IO::ReadIfExists($file);

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
			self::CheckDailyLimits();
		}

		PerformanceDaylyUsageLock::EndWrite();
		if (Context::Settings()->Performance()->PerformancePerUser)
			PerformanceDaylyUserLock::EndWrite();
		PerformanceDaylyLocksLock::EndWrite();
	}

	private static function CheckDailyLimits() : void
	{
		$diskInfo = System::GetDiskInfoBytes();

		$storageMB = round($diskInfo['Storage'] / 1024 / 1024, 10);
		if ($storageMB < Context::Settings()->Limits()->WarningMinimumFreeStorageSpaceMB)
		{
			Performance::SendPerformanceWarning(
				'espacio en disco en Storage',
				Context::Settings()->Limits()->WarningMinimumFreeStorageSpaceMB . ' MB', $storageMB . ' MB');
		}

		$systemMB = round($diskInfo['System'] / 1024 / 1024, 10);
		if ($systemMB < Context::Settings()->Limits()->WarningMinimumFreeSystemSpaceMB)
		{
			Performance::SendPerformanceWarning(
				'espacio en disco en Sistema Operativo',
				Context::Settings()->Limits()->WarningMinimumFreeSystemSpaceMB . ' MB', $systemMB . ' MB'
			);
		}
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
		return IO::ReadIfExists($daylyProcessor);
	}

	public static function ReadTodayExtraValues(string $key) : ?int
	{
		$extras = Context::ExtraHitsLabels();
		$index = Arr::indexOf($extras, $key);
		if ($index === -1)
			return null;
		$days = self::ReadDaysValues();
		$key = Date::GetLogDayFolder();
		if (isset($days[$key]) == false)
			return null;
		$record = PerformanceItem::Parse($days[$key]);
		return isset($record->extraHits[$index]) ? (int)$record->extraHits[$index] : null;
	}

	private static function CheckErrorLimits(array $extraHits) : void
	{
		// Controla errores
		$labels = Context::ExtraHitsLabels();
		$i = Arr::IndexOf($labels, 'Errores');
		if ($i === -1)
			return;

		$dailyErrorCount = $extraHits[$i];
		if (self::$errorCount > 0 && $dailyErrorCount == Context::Settings()->Limits()->WarningDaylyErrors)
		{
			Performance::SendPerformanceWarning(
				'errores diarios',
				Context::Settings()->Limits()->WarningDaylyErrors . ' errores', $dailyErrorCount . ' errores');
		}
	}

	private static function SaveDaylyUsage(int $ellapsedMilliseconds) : array
	{
		$days = self::ReadDaysValues();
		$key = Date::GetLogDayFolder();

		$prevRecord = self::ReadCurrentKeyValues($days, $key);

		$extraHits = Context::ExtraHits();

		$currentValues = self::IncrementLargeKey($days, $key, $ellapsedMilliseconds, self::$hitCount, self::$lockedMs,
			Request::IsGoogle(), self::$mailsSent, self::$dbMs, self::$dbHitCount, $extraHits);

		self::CheckErrorLimits($currentValues[7]);

		$daylyProcessor = self::ResolveFilenameDayly();
		IO::WriteIniFile($daylyProcessor, $days);

		return [
			'days' => $days,
			'key' => $key,
			'prevHits' => $prevRecord->hits,
			'prevDuration' => $prevRecord->duration,
			'prevLock' => $prevRecord->locked,
		];
	}

	public static function SaveTotalGroupedEmails(string $text, string $month = '') : void
	{
		$file = self::ResolveFilenameTotalGroupedEmails($month);
		IO::WriteAllText($file, $text);
	}

	public static function SaveTotalEmails(int $cant) : int
	{
		$file = self::ResolveFilenameTotalEmails();
		$total = $cant;
		if(file_exists($file))
			$total += (int)file_get_contents($file);

		IO::WriteAllText($file, (string)$total);
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
			$current = IO::ReadIfExists($path);

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
		$record = self::ReadCurrentKeyValues($days, $key);
		$hits = $record->hits;
		$duration = $record->duration;
		$locked = $record->locked;

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
		$host = Str::Replace(Context::Settings()->Servers()->Current()->publicUrl, "https://", "");
		$mail = new Mail();
		$mail->to = Context::Settings()->Mail()->NotifyAddress;
		$mail->subject = 'ALERTA ADMINISTRATIVA de ' . Context::Settings()->applicationName . ' ' . $server . ' (' . $metric . ' > ' . $limit . ') - ' . $host;
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

	private static function ReadCurrentKeyValues(array $arr, string $key) : PerformanceItem
	{
		return isset($arr[$key]) ? PerformanceItem::Parse($arr[$key]) : new PerformanceItem();
	}

	private static function IncrementKey(array &$arr, $key, int $value, int $newHits, int $newLocked, int $newDbMs, int $newDbHitCount) : void
	{
		$record = self::ReadCurrentKeyValues($arr, $key);
		$record->Add(new PerformanceItem($newHits, $value, $newLocked, 0, 0, $newDbMs, $newDbHitCount));
		$arr[$key] = $record->ToStringMedium();
	}

	private static function IncrementLockKey(array &$arr, string $key, int $value, int $newHits, int $newLocked) : void
	{
		$record = self::ReadCurrentKeyValues($arr, $key);
		$record->Add(new PerformanceItem($newHits, $value, $newLocked));
		$arr[$key] = $record->ToStringShort();
	}

	private static function IncrementLargeKey(array &$arr, $key, int $value, int $newHits, int $newLocked,
		bool $isGoogleHit, int $mailCount, int $newDbMs, int $newDbHits, array $newExtraHits = []) : array
	{
		if (isset($arr[$key]))
		{
			$record = PerformanceItem::Parse($arr[$key]);
			$mergedExtraHits = $record->MergeExtraHitsInto($newExtraHits);
		}
		else
		{
			$record = new PerformanceItem();
			$mergedExtraHits = $newExtraHits;
		}

		$record->Add(new PerformanceItem($newHits, $value, $newLocked, (int)$isGoogleHit, $mailCount, $newDbMs, $newDbHits));
		$record->extraHits = $mergedExtraHits;

		$arr[$key] = $record->ToStringLong();

		return [$record->hits, $record->duration, $record->locked, $record->google, $record->mails,
			$record->dbMs, $record->dbHits, $mergedExtraHits];
	}

	public static function ResolveFolder(string $month = '') : string
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

	public static function ResolveFilenameTotalGroupedEmails(string $month = '') : string
	{
		$path = self::ResolveFolder($month);
		return $path . '/totalGroupedEmails.txt';
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
}