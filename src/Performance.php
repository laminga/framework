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

	private static function GetEmptyData() : array
	{
		return [
			'hits' => 0,
			'duration' => 0,
			'locked' => 0,
			'p4' => 0,
			'p5' => 0,
			'p6' => 0,
			'p7' => 0,
			'extra' => [],
		];
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

		$vals = IO::ReadIfExists($file);
		$vals = self::IncrementKey($vals, $keyMs, $ellapsedMilliseconds, self::$hitCount, self::$lockedMs, self::$dbMs, self::$dbHitCount);
		// graba
		IO::WriteIniFile($file, $vals);
	}

	private static function SaveControllerUsage(int $ellapsedMilliseconds, string $month = '') : void
	{
		$file = self::ResolveFilename($month);
		$keyMs = self::$method;

		$vals = IO::ReadIfExists($file);
		$vals = self::IncrementKey($vals, $keyMs, $ellapsedMilliseconds, self::$hitCount, self::$lockedMs, self::$dbMs, self::$dbHitCount);
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
		$todayFolder = '';
		if (file_exists($path))
			$todayFolder = IO::ReadAllText($path);

		if ($today != $todayFolder)
		{
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
		if ($systemMB < Context::Settings()->Limits()->WarningMinimumFreeSystemSpaceMB) {
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

	public static function ReadTodayExtraValues(string $key) : int
	{
		$extras = Context::ExtraHitsLabels();
		$index = Arr::IndexOf($extras, $key);
		if ($index == -1)
			return 0;
		$days = self::ReadDaysValues();
		$key = Date::GetLogDayFolder();
		$data = self::ReadCurrentKeyValues($days, $key);
		if (isset($data['extra'][$index]))
			return $data['extra'][$index];

		return 0;
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

		$data = self::ReadCurrentKeyValues($days, $key);

		$extraHits = Context::ExtraHits();

		$days = self::IncrementLargeKey($days, $key, $ellapsedMilliseconds, self::$hitCount, self::$lockedMs,
			Request::IsGoogle(), self::$mailsSent, self::$dbMs, self::$dbHitCount, $extraHits);

		self::CheckErrorLimits($extraHits);

		$daylyProcessor = self::ResolveFilenameDayly();
		IO::WriteIniFile($daylyProcessor, $days);

		return [
			'days' => $days,
			'key' => $key,
			'prevHits' => $data['hits'],
			'prevDuration' => $data['duration'],
			'prevLock' => $data['locked'],
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
			$current = IO::ReadIfExists($path);

			foreach(self::$locksByClass as $key => $value)
				$current = self::IncrementLockKey($current, $key, $value[2], $value[0], $value[1]);

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
		$data = self::ReadCurrentKeyValues($days, $key);

		$maxMs = Context::Settings()->Limits()->WarningDaylyExecuteMinutes * 60 * 1000;
		$maxLockMs = Context::Settings()->Limits()->WarningDaylyLockMinutes * 60 * 1000;
		$maxRequestSeconds = Context::Settings()->Limits()->WarningRequestSeconds;

		$maxHits = Context::Settings()->Limits()->WarningDaylyHits;
		if ($prevHits < $maxHits && $data['hits'] >= $maxHits)
			self::SendPerformanceWarning('hits', $maxHits . ' hits', $data['hits'] . ' hits');
		if ($prevDuration < $maxMs && $data['duration'] >= $maxMs)
			self::SendPerformanceWarning('minutos de CPU', self::Format($maxMs, 1000 * 60, 'minutos'), self::Format($data['duration'], 1000 * 60, 'minutos'));
		if ($prevLocked < $maxLockMs && $data['locked'] >= $maxLockMs)
			self::SendPerformanceWarning('tiempo de locking', self::Format($maxLockMs, 1000 * 60, 'minutos'), self::Format($data['locked'], 1000 * 60, 'minutos'));

		if ($ellapsedMilliseconds >= $maxRequestSeconds * 1000 && self::$allowLongRunningRequest == false)
			Log::HandleSilentException(new PublicException('El pedido ha excedido los ' . $maxRequestSeconds . ' segundos de ejecución. Tiempo transcurrido: ' . $ellapsedMilliseconds . ' ms.'));

		// Se fija si tiene que pasar a 'defensive Mode'
		$defensiveThreshold = Context::Settings()->Limits()->DefensiveModeThresholdDaylyHits;
		if ($data['hits'] >= $defensiveThreshold && Traffic::IsInDefensiveMode() == false)
		{
			Traffic::GoDefensiveMode();
			self::SendPerformanceWarning('activación de modo defensivo', $defensiveThreshold . ' hits', $data['hits'] . ' hits');
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

	private static function ReadCurrentKeyValues(array $arr, string $key) : array
	{
		if (isset($arr[$key]))
			return self::ParseHit($arr[$key]);
		return self::GetEmptyData();
	}

	private static function IncrementKey(array $arr, string $key, int $value, int $newHits, int $newLocked, int $newDbMs, int $newDbHitCount) : array
	{
		$data = [
			'hits' => $newHits,
			'duration' => $value,
			'locked' => $newLocked,
			'p4' => $newDbMs,
			'p5' => $newDbHitCount,
		];
		if (isset($arr[$key]))
		{
			$data = self::ParseHit($arr[$key]);

			$data['hits'] += $newHits;
			$data['duration'] += $value;
			$data['locked'] += $newLocked;
			$data['p4'] += $newDbMs;
			$data['p5'] += $newDbHitCount;
		}
		$arr[$key] = $data['hits'] . ';' . $data['duration'] . ';' . $data['locked'] . ';' . $data['p4'] . ';' . $data['p5'];
		return $arr;
	}

	private static function IncrementLockKey(array $arr, string $key, int $value, int $newHits, int $newLocked) : array
	{
		$data = [
			'hits' => $newHits,
			'duration' => $value,
			'locked' => $newLocked,
		];
		if (isset($arr[$key]))
		{
			$data = self::ParseHit($arr[$key]);

			$data['hits'] += $newHits;
			$data['duration'] += $value;
			$data['locked'] += $newLocked;
		}
		$arr[$key] = $data['hits'] . ';' . $data['duration'] . ';' . $data['locked'];
		return $arr;
	}

	private static function IncrementLargeKey(array $arr, string $key, int $value, int $newHits, int $newLocked,
		bool $isGoogleHit, int $mailCount, int $newDbMs, int $newDbHits, array &$newExtraHits) : array
	{
		$data = [
			'hits' => $newHits,
			'duration' => $value,
			'locked' => $newLocked,
			'p4' => (int)$isGoogleHit,
			'p5' => $mailCount,
			'p6' => $newDbMs,
			'p7' => $newDbHits,
		];

		if (isset($arr[$key]))
		{
			$data = self::ParseHit($arr[$key]);
			$data['hits'] += $newHits;
			$data['duration'] += $value;
			$data['locked'] += $newLocked;
			$data['p4'] += (int)$isGoogleHit;
			$data['p5'] += $mailCount;
			$data['p6'] += $newDbMs;
			$data['p7'] += $newDbHits;
			for($n = 0; $n < count($newExtraHits); $n++)
			{
				if($n < count($data['extra']) && isset($data['extra'][$n]))
					$newExtraHits[$n] += $data['extra'][$n];
			}
		}
		$arr[$key] = $data['hits'] . ';' . $data['duration'] . ';' . $data['locked'] . ';' . $data['p4']
			. ';' . $data['p5'] . ';' . $data['p6'] . ';' . $data['p7'] . ';' . implode(',', $newExtraHits);

		return $arr;
	}

	private static function ParseHit(string $value) : array
	{
		$parts = explode(';', $value);

		$ret = self::GetEmptyData();
		$ret['hits'] = (int)$parts[0];
		$ret['duration'] = (int)$parts[1];

		if (count($parts) > 2)
			$ret['locked'] = (int)$parts[2];

		if (count($parts) > 3)
		{
			$ret['p4'] = (int)$parts[3];
			$ret['p5'] = (int)$parts[4];
		}

		if (count($parts) > 5)
			$ret['p6'] = (int)$parts[5];

		if (count($parts) > 6)
			$ret['p7'] = (int)$parts[6];

		if (count($parts) > 7)
			$ret['extra'] = array_map('intval', explode(',', $parts[7]));

		return $ret;
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
		$days = IO::ReadIfExists($path);

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
			$data = self::ParseHit($value);
			$google = $data['p4'];
			$mails = $data['p5'];
			$dbMs = $data['p6'];
			$dbHits = $data['p7'];

			$dataHitRow[] = $data['hits'];
			$totalsDataHitRow += $data['hits'];

			$googleRow[] = $google;
			$totalsGoogleRow += $google;
			$mailRow[] = $mails;
			$totalsMailRow += $mails;

			$dataMsRow[] = round($data['duration'] / 1000 / 60, 1);
			$totalsDataMsRow += $data['duration'];
			$dataAvgRow[] = round($data['duration'] / $data['hits']);
			$totalsAvgRow += ($data['duration'] / $data['hits']);
			$dataLockedRow[] = round($data['locked'] / 1000, 1);
			$totalsDataLockedRow += $data['locked'];
			$dataDbMsRow[] = round($dbMs / 1000 / 60, 1);
			$totalsDataDbMsRow += $dbMs;
			$dataDbHitRow[] = $dbHits;
			$totalsDataDbHitRow += $dbHits;

			for($n = 0; $n < count($extraValues); $n++)
			{
				if ($n < count($data['extra']))
				{
					$extraValues[$n][] = $data['extra'][$n];
					if (isset($data['extra'][$n]))
						$totalsExtraValues[$n] += $data['extra'][$n];
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

	private static function Average(float $a, int $b) : string
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
		{
			if (Str::StartsWith($months[$n], '2'))
				$actualMoths[] = $months[$n];
		}

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
						$data = IO::ReadIfExists($path . '/' . $file);
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
					$data = self::ParseHit($values[$method]);
					$totalMinutes += $data['duration'] / 1000 / 60;
				}
			}
		}
		// genera celdas
		foreach($controllers as $controller => $values)
		{
			$cells = [0, 0, 0, 0];
			$controllerHits = 0;
			$controllerTime = 0;
			$dbControllerHits = 0;
			$dbControllerMs = 0;
			foreach($methods as $method)
			{
				$data = [ 'hits' => '-', ];
				$avg = '-';
				$db = '-';
				$share = '-';
				if (isset($values[$method]))
				{
					$data = self::ParseHit($values[$method]);
					$dbMs = $data['p4'];
					$dbHits = $data['p5'];

					$controllerHits += $data['hits'];
					$avg = round($data['duration'] / $data['hits']);
					$controllerTime += $data['duration'];
					$share = self::FormatShare($data['duration'], $totalDuration);
					$db = round($dbMs / $data['hits']) . ' (' . round($dbHits / $data['hits'], 1) . ')';
					$dbControllerHits += $dbHits;
					$dbControllerMs += $dbMs;
				}
				$cells[] = $data['hits'];
				$cells[] = $avg;
				$cells[] = $db;
				$cells[] = $share;
			}
			$cells[0] = '-';
			$cells[1] = '-';
			$cells[2] = '-';
			if ($controllerHits > 0)
			{
				$cells[0] = $controllerHits;
				$cells[1] = round($controllerTime / $controllerHits);
				$cells[2] = round($dbControllerMs / $controllerHits) . ' (' . round($dbControllerHits / $controllerHits, 1) . ')';
			}
			$cells[3] = self::FormatShare($controllerTime, $totalDuration);

			if ($controller == '')
				$controller = 'n/d';
			$rows[$controller] = $cells;
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

		if ($month == 'dayly' || $month == 'yesterday')
			$lock = new PerformanceDaylyLocksLock();
		else
			$lock = new PerformanceMonthlyLocksLock();

		$lock->LockRead();

		$path = self::ResolveFilenameLocks($month);
		$rows = IO::ReadIfExists($path);
		$lock->Release();

		ksort($rows);

		$ret = ['Clase' => ['Locks', 'Promedio (ms)', 'Total (seg.)']];

		foreach($rows as $key => $value)
		{
			$data = self::ParseHit($value);
			$avg = '-';
			if ($data['duration'] > 0)
				$avg = round($data['locked'] / $data['duration']);

			$cells = [
				$data['hits'] . ($data['duration'] > 0 ? ' (' . $data['duration'] . ')' : ''),
				$avg,
				$data['locked'] / 1000,
			];

			$ret[$key . ($data['duration'] > 0 ? ' (waited)' : '')] = $cells;
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
				$data = self::ParseHit($value);
				$ret += $data['duration'];
			}
		}
		return $ret;
	}
}

