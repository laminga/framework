<?php

namespace minga\framework;

use minga\framework\locking\PerformanceDaylyLocksLock;
use minga\framework\locking\PerformanceDaylyUsageLock;
use minga\framework\locking\PerformanceDaylyUserLock;
use minga\framework\locking\PerformanceMonthlyDayLock;
use minga\framework\locking\PerformanceMonthlyLocksLock;
use minga\framework\locking\PerformanceMonthlyUsageLock;
use minga\framework\locking\PerformanceMonthlyUserLock;

/**
 * Lee los datos que registra Performance y arma las tablas usadas para presentación
 * (pantallas de administración). No escribe datos; toda la escritura queda en Performance.
 */
class PerformanceTable
{
	public static function GetDaylyTable(string $month, bool $appendTotals = false) : array
	{
		$lock = new PerformanceMonthlyDayLock();
		$lock->LockRead();

		$path = Performance::ResolveFilenameDayly($month);
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
			$record = PerformanceItem::Parse($value);

			$headerRow[] = $key;
			$dataHitRow[] = $record->hits;
			$totalsDataHitRow += $record->hits;

			$googleRow[] = $record->google;
			$totalsGoogleRow += $record->google;
			$mailRow[] = $record->mails;
			$totalsMailRow += $record->mails;

			$dataMsRow[] = round($record->duration / 1000 / 60, 1);
			$totalsDataMsRow += $record->duration;
			$dataAvgRow[] = round($record->duration / $record->hits);
			$totalsAvgRow += ($record->duration / $record->hits);
			$dataLockedRow[] = round($record->locked / 1000, 1);
			$totalsDataLockedRow += $record->locked;
			$dataDbMsRow[] = round($record->dbMs / 1000 / 60, 1);
			$totalsDataDbMsRow += $record->dbMs;
			$dataDbHitRow[] = $record->dbHits;
			$totalsDataDbHitRow += $record->dbHits;

			for($n = 0; $n < count($extraValues); $n++)
			{
				if ($n < count($record->extraHits))
				{
					$extraValues[$n][] = $record->extraHits[$n];
					if ($record->extraHits[$n])
						$totalsExtraValues[$n] += $record->extraHits[$n];
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

		if ($controller === 'logs')
			$controller = 'logs/activity';
		if ($controller === 'admin')
			$controller = 'admin/activity';

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

		$path = Performance::ResolveFolder($month);
		$rows = [];

		$controllers = [];
		// lee los datos desde disco
		foreach(IO::GetFiles($path, '.txt') as $file)
		{
			if ($file != 'processor.txt' && $file != 'locks.txt'
				&& $file != 'totalEmails.txt' && $file != 'totalGroupedEmails.txt')
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
		$rows['&nbsp;'] = $methodsPlusTotal;
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
					$record = PerformanceItem::Parse($values[$method]);
					$totalMinutes += $record->duration / 1000 / 60;
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
				if (isset($values[$method]))
				{
					$record = PerformanceItem::Parse($values[$method]);
					$hits = $record->hits;
					$duration = $record->duration;
					$dbMs = $record->dbMs;
					$dbHits = $record->dbHits;

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

			if ($controller == '')
				$rows['n/d'] = $cells;
			else
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

	public static function GetMailsTable(string $month) : array
	{
		return MailLogSummarizer::GetTotals($month);
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

		$path = Performance::ResolveFilenameLocks($month);
		$rows = IO::ReadIfExists($path);
		$lock->Release();

		ksort($rows);

		$ret = ['Clase' => ['Locks', 'Promedio (ms)', 'Total (seg.)']];

		foreach($rows as $key => $value)
		{
			$record = PerformanceItem::Parse($value);
			$hits = $record->hits;
			$waited = $record->duration;
			$locked = $record->locked;

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

	private static function CalculateTotalDuration(array $controllers) : int
	{
		$ret = 0;
		foreach($controllers as $values)
		{
			foreach($values as $value)
			{
				$record = PerformanceItem::Parse($value);
				$ret += $record->duration;
			}
		}
		return $ret;
	}
}