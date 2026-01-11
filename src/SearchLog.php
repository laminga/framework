<?php

namespace minga\framework;

use minga\framework\locking\SearchLogLock;

class SearchLog
{
	private $timeStart = 0;

	public function BeginSearch() : void
	{
		$this->timeStart = microtime(true);
	}

	public function RegisterSearch($text, $matches) : void
	{
		try
		{
			Profiling::BeginTimer();
			if (is_array($text))
				$text = Arr::AssocToString($text, true, true);
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

	private function Save($text, $matches) : void
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
		return $path . '/' . $item . '.txt';
	}

	private static function SaveSearchHit($block, $text, $matches, $ellapsedMs) : void
	{
		$file = self::ResolveFile($block);
		$line = self::CreateKey($text, $matches, $ellapsedMs);
		// graba
		IO::AppendLine($file, $line);
	}

	private static function CreateKey($text, $matches, $ellapsedMs) : string
	{
		$user = Context::LoggedUser();
		if ($user == false)
			$user = PhpSession::SessionId();
		$now = Date::FormattedArNow();

		if(is_array($text) || is_array($matches))
			return '';

		return $user . "\t" . $now . "\t" . $matches . "\t" . Str::ReplaceGroup($text, ["\t", "\n", "\r"], ' ') . "\t" . $ellapsedMs;
	}

	private static function ParseHit($value, &$user, &$dateTime, &$text, &$matches, &$ellapsed)
	{
		if ($value == null)
			return false;
		$parts = explode("\t", $value);
		$user = $parts[0];
		$dateTime = $parts[1];
		$matches = $parts[2];
		$text = $parts[3];
		$ellapsed = $parts[4];
		return true;
	}

	public static function GetSearchTable($month = '', $includeHeaders = false)
	{
		$lock = new SearchLogLock();
		$lock->LockRead();

		if ($month == '') $month = 'dayly';
		$currentMonth = Date::GetLogMonthFolder();
		if ($month !== 'dayly')
			$path = self::ResolveFile($month);
		else
			$path = self::ResolveFile($currentMonth);

		if (IO::Exists($path))
		{
			$rows = IO::ReadAllLines($path);
		}
		else
			$rows = [];
		$lock->Release();

		$ret = [];
		if ($includeHeaders)
			$ret['Id'] = ['Fecha', 'Búsqueda', 'Resultados', 'Duración (ms)', 'Usuario o sesión'];

			$currentDay = Date::FormattedArDate();
		for ($n = count($rows) - 1; $n >= 0; $n--)
		{
			$line = $rows[$n];
			if (self::ParseHit($line, $user, $dateTime, $text, $matches, $ellapsed))
			{
				if ($month !== 'dayly' || Str::StartsWith($dateTime, $currentDay))
				{
					$cells = [$dateTime, $text, $matches, $ellapsed, $user];
					$ret["" . (count($rows) - $n - 1)] = $cells;
				}
			}
		}
		return $ret;
	}
}
