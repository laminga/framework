<?php

namespace minga\framework;

use minga\framework\locking\SearchLogLock;

class SearchLog
{
	private static $time_start = null;

	public static function BeginSearch()
	{
		self::$time_start = microtime(true);
	}

	public static function RegisterSearch($text, $matches)
	{
		Profiling::BeginTimer();
		try
		{
			self::Save($text, $matches);
		}
		catch(\Exception $e)
		{
			Log::HandleSilentException($e);
		}
		Profiling::EndTimer();
	}

	private static function Save($text, $matches)
	{
		$lock = new SearchLogLock();

		$ellapsedMs = round((microtime(true) - self::$time_start) * 1000);

		$lock->LockWrite();
		$month = Date::GetLogMonthFolder();
		self::SaveSearchHit('dayly', $text, $matches, $ellapsedMs);
		self::SaveSearchHit($month, $text, $matches, $ellapsedMs);
		$lock->Release();
	}

	public static function DayCompleted($newDay)
	{
		$file = self::ResolveFile('dayly');
		$fileYesterday = self::ResolveFile('yesterday');
		IO::Move($file, $fileYesterday);
	}

	private static function ResolveFile($item = '')
	{
		$path = Context::Paths()->GetSearchLogLocalPath();
		IO::EnsureExists($path);
		$ret = $path . '/' . $item . ".txt";
		return $ret;
	}

	private static function SaveSearchHit($block, $text, $matches, $ellapsedMs)
	{
		$file = self::ResolveFile($block);
		$line = self::CreateKey($text, $matches, $ellapsedMs);
		// graba
		IO::AppendLine($file, $line);
	}

	private static function CreateKey($text, $matches, $ellapsedMs)
	{
		$user = Context::LoggedUser();
		if (!$user) $user = PhpSession::SessionId();
		$now = Date::FormattedArNow();

		$value = $user . "\t" . $now . "\t" .  $matches . "\t" . Str::Replace($text, '\t', ' ') . "\t" . $ellapsedMs;
		return $value;
	}

	private static function ParseHit($value, &$user, &$dateTime, &$text, &$matches, &$ellapsed)
	{
		if ($value == null) return false;
		$parts = explode("\t", $value);
		$user = $parts[0];
		$dateTime = $parts[1];
		$matches = $parts[2];
		$text = $parts[3];
		$ellapsed = $parts[4];
		return true;
	}

	private static function ReadIfExists($file)
	{
		if (file_exists($file))
			return IO::ReadAllLines($file);
		else
			return array();
	}

	public static function GetSearchTable($month)
	{
		$lock = new SearchLogLock();
		$lock->LockRead();

		if ($month == '') $month = 'dayly';
		$path = self::ResolveFile($month);
		$rows = self::ReadIfExists($path);
		$lock->Release();

		$ret = array();
		$ret['Fecha'] = array('Búsqueda', 'Resultados', 'Duración (ms)', 'Usuario o sesión');

		foreach($rows as $line)
		{
			if (self::ParseHit($line, $user, $dateTime, $text, $matches, $ellapsed))
			{
				$cells = array($text, $matches, $ellapsed, $user);

				$ret[$dateTime] = $cells;
			}
		}
		return $ret;
	}
}
