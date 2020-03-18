<?php

namespace minga\framework;

use minga\classes\fulltext\Catalog;
use minga\framework\locking\SearchLogLock;

class SearchLog
{
	private $timeStart = 0;

	public function BeginSearch()
	{
		$this->timeStart = microtime(true);
	}

	public function RegisterSearch($text, $matches)
	{
		try
		{
			Profiling::BeginTimer();
			if (is_array($text))
			{
				if(isset($text['Page']))
					$text['Page'] = ($text['Page'] / Catalog::MAX_RESULTS) + 1;
				$text = Arr::AssocToString($text, true, true);
			}
			$this->Save($text, $matches);
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

	private function Save($text, $matches)
	{
		$lock = new SearchLogLock();

		$ellapsedMs = round((microtime(true) - $this->timeStart) * 1000);

		$lock->LockWrite();
		$month = Date::GetLogMonthFolder();
		self::SaveSearchHit($month, $text, $matches, $ellapsedMs);
		$lock->Release();
	}

	private static function ResolveFile($item = '')
	{
		$path = Context::Paths()->GetSearchLogLocalPath();
		IO::EnsureExists($path);
		$ret = $path . '/' . $item . '.txt';
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
		if ($user == false)
			$user = PhpSession::SessionId();
		$now = Date::FormattedArNow();

		if(is_array($text) || is_array($matches))
			return '';

		return $user . "\t" . $now . "\t" .  $matches . "\t" . Str::Replace($text, "\t", ' ') . "\t" . $ellapsedMs;
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
		return [];
	}

	public static function GetSearchTable($month)
	{
		$lock = new SearchLogLock();
		$lock->LockRead();

		if ($month == '') $month = 'dayly';
		$currentMonth = Date::GetLogMonthFolder();
		if ($month !== 'dayly')
			$path = self::ResolveFile($month);
		else
			$path = self::ResolveFile($currentMonth);

		$rows = self::ReadIfExists($path);
		$lock->Release();

		$ret = [];
		$ret['Fecha'] = ['Búsqueda', 'Resultados', 'Duración (ms)', 'Usuario o sesión'];

		$currentDay = Date::FormattedArDate();
		for($n = count($rows) - 1; $n >= 0; $n--)
		{
			$line = $rows[$n];
			if (self::ParseHit($line, $user, $dateTime, $text, $matches, $ellapsed))
			{
				if ($month !== 'dayly' || Str::StartsWith($dateTime, $currentDay))
				{
					$cells = [$text, $matches, $ellapsed, $user];
					$ret[$dateTime] = $cells;
				}
			}
		}
		return $ret;
	}
}
