<?php

namespace minga\framework;

class Date
{

	public static function GetLogDayFolder()
	{
		return date("d");
	}

	public static function GetLogMonthFolder($offset = 0)
	{
		$time = mktime(0, 0, 0, (int)date("m") + $offset, 1, (int)date("Y"));
		return date("Y-m", $time);
	}

	public static function NowGMT($offset)
	{
		if ($offset < -12 || $offset > 12)
			throw new ErrorException("Time offset out of range: " . $offset);
		return self::UniversalNow() + 60 * 60 * $offset;
	}

	public static function UniversalNow()
	{
		$virtual = PhpSession::GetSessionValue('now');
		if ($virtual == '')
			return time();
		else
			return intval($virtual);
	}

	public static function ChangeUniversalNow($time)
	{
		PhpSession::SetSessionValue('now', $time);
	}

	public static function ArNow()
	{
		return self::NowGMT(-3);
	}

	public static function FormattedArDate()
	{
		return self::FormattedDateOnly(self::ArNow());
	}

	public static function FormattedArNow()
	{
		return self::FormattedDate(self::ArNow());
	}

	public static function FormattedDateOnly($date)
	{
		return date("Y-m-d", $date);
	}

	public static function DateToDDMMYYYY($date)
	{
		return date("d/m/Y", $date);
	}

	public static function FormattedDate($date)
	{
		return date("Y-m-d@H.i.s", $date);
	}

	public static function DbDate($date)
	{
		return date("Y-m-d H:i:s", $date);
	}

	public static function ConvertFormattedDateDDMMYYYYHHMM($date)
	{
		if ($date == "") return "";
		return substr($date, 8, 2) . '/' . substr($date, 5, 2).'/' .
			substr($date, 0, 4) . ' ' . substr($date, 11, 2) . ':' . substr($date, 14, 2);
	}

	public static function ConvertFormattedDateDDMMYYYY($date)
	{
		if ($date == "") return "";
		return substr($date, 8, 2) . '/' . substr($date, 5, 2).'/' .
			substr($date, 0, 4);
	}

	public static function ConvertFromDDMMYYYYToYYYYMMDD($date)
	{
		if ($date == "") return "";
		if (strlen(trim($date)) != 10)
			$date = self::AddZerosInDate($date);
		return substr($date, 6, 4) . '-' . substr($date, 3, 2).'-' . substr($date, 0, 2);
	}

	public static function AbsoluteMonth($date)
	{
		return self::DateTimeGetYear($date) * 12 + self::DateTimeGetMonth($date) - 1;
	}
	
	public static function DateTimeGetMonth($date)
	{
		return intval($date->format('m'));
	}
	public static function DateTimeGetYear($date)
	{
		return intval($date->format('Y'));
	}

	public static function ParseTime($span)
	{
		if ($span == "")
			return -1;
		$span = strtolower($span);
		$span = str_replace("hs", "", $span);
		$parts = explode(':', $span);
		$minutes = 60 * (int)$parts[0];
		if (count($parts) > 1)
			$minutes += (int)$parts[1];
		return $minutes;
	}

	public static function FormatTime($minutes)
	{
		$mod = $minutes % 60;
		$ret = floor($minutes / 60);
		$ret .= ":" . ($mod <= 9 ? '0' : '') . $mod;
		return $ret;
	}

	public static function ParseSpan($span)
	{
		if($span == '')
			return 0;
		$span = strtolower($span);
		$span = str_replace("hs", "", $span);
		$parts = explode(':', $span);
		$minutes = 60 * (int)$parts[0];
		if (count($parts) > 1)
			$minutes += (int)$parts[1];
		return $minutes;
	}

	public static function FormatSpan($minutes)
	{
		if ($minutes == "")
			return "";
		$mod = $minutes % 60;
		$ret = floor($minutes / 60);
		if ($mod > 0)
		  	$ret .= ":" . ($mod <= 9 ? '0' : '') . $mod;
		return $ret . "hs";
	}

	private static function AddZerosInDate($date)
	{
		$date = str_replace("-", "/", $date);
		$date = str_replace(" ", "", $date);
		$parts = explode('/', $date);
		if (count($parts) != 3)
			return $date;
		if (strlen($parts[0]) == 1)
			$parts[0] = '0' . $parts[0];
		if (strlen($parts[1]) == 1)
			$parts[1] = '0' . $parts[1];
		if (strlen($parts[2]) == 2)
			$parts[2] = '20' . $parts[2];
		return $parts[0] . '/' . $parts[1] . '/' . $parts[2];
	}

	public static function UserFormattedAr($date)
	{
		return date("d/m/Y g:i:s (\G\M\T-3)", $date - 60 * 60 * 3);
	}

	public static function DbArNow()
	{
		return self::DbDate(self::ArNow());
	}

	public static function DateTimeArNow()
	{
		$date = new \DateTime;
		$date->setTimestamp(self::ArNow());
		return $date;
	}

	public static function DaysDiff($date1, $date2)
	{
		$interval = date_diff($date1, $date2);
		return intval($interval->format('%a'));
	}

	public static function DateTimeNow()
	{
		$date = new \DateTime;
		$date->setTimestamp(time());
		return $date;
	}

	public static function DateTimeToday()
	{
		$date = date_create(self::Today());
		return $date;
	}

	public static function Today()
	{
		return date("Y-m-d");
	}

	public static function CurrentYear()
	{
		return self::GetYearFromDay(date("Y-m-d"));
	}

	public static function GetYearMonthFromDay($day)
	{
		// formato: 2015-02-29
		return substr($day, 0, 7);
	}

	public static function GetYearFromDay($day)
	{
		// formato: 2015-02-29
		return substr($day, 0, 4);
	}

	public static function GetMonthFromDay($day)
	{
		// formato: 2015-02-29
		return substr($day, 5, 2);
	}

	public static function FormatDateText($Year, $Month, $Day)
	{
		$ret = $Day . " de " . Str::ToLower(self::MonthToString($Month));
		if ($Year != "")
			$ret .=  " de " . $Year;
		return $ret;
	}

	public static function WeekDayToString($day)
	{
		switch((int)$day)
		{
			case 1:
				return 'Lunes';
			case 2:
				return 'Martes';
			case 3:
				return 'Miércoles';
			case 4:
				return 'Jueves';
			case 5:
				return 'Viernes';
			case 6:
				return 'Sábado';
			case 7:
				return 'Domingo';
			default:
				return '';
		}
	}

	public static function MonthToString($prevMonth)
	{
		$prevMonth .= "";

		switch((int)$prevMonth)
		{
			case 1:
				return 'Enero';
			case 2:
				return 'Febrero';
			case 3:
				return 'Marzo';
			case 4:
				return 'Abril';
			case 5:
				return 'Mayo';
			case 6:
				return 'Junio';
			case 7:
				return 'Julio';
			case 8:
				return 'Agosto';
			case 9:
				return 'Septiembre';
			case 10:
				return 'Octubre';
			case 11:
				return 'Noviembre';
			case 12:
				return 'Diciembre';
			default:
				return '';
		}
	}

	public static function FormattedDateToDateTime($date)
	{
		return \DateTime::createFromFormat("Y-m-d@H.i.s", $date);
	}

	/*
	 * Compara una fecha ($date) sumándole días ($days)
	 * con "now".
	 * Si esa fecha + días es mayor a now devuelve true,
	 * es decir, no está pasada la fecha de vencimiento,
	 * si no, false.
	 *
	 * $date en formato de FormattedDate().
	 * $days int
	 *
	 * return bool
	 *
	 */
	public static function DateNotPast($date, int $days) : bool
	{
		if($date == '')
			return false;

		if($days <= 0)
			throw new ErrorException('Days must be a positive integer');

		$dt = self::FormattedDateToDateTime($date);
		if($dt === false)
			throw new ErrorException('Invalid Date');

		$dt->add(new \DateInterval('P' . $days . 'D'));
		$now = new \DateTime('now');

		return $dt > $now;
	}

	public static function TryParseDate($date, &$day, &$month, &$year)
	{
		$date = str_replace("-", "/", $date);
		$date = str_replace(" ", "", $date);
		$parts = explode('/', $date);
		if (count($parts) != 3)
		  	return false;
		for($i = 0; $i < count($parts); $i++)
		{
			$prev = $parts[$i];
			$parts[$i] = (int)$parts[$i];
			if ((string)$parts[$i] != $prev)
				return false;
		}
		if (checkdate((int)$parts[1] , (int)$parts[0] , (int)$parts[2]) == false)
			return false;
		$day = $parts[0];
		$month = $parts[1];
		$year = $parts[2];
		return true;
	}

	public static function ParseDate($date)
	{
		$day = null;
		$month = null;
		$year = null;
		$bret = self::TryParseDate($date, $day, $month, $year);
		if ($bret == false)
			throw new ErrorException('Invalid date.');
		return $year . '-' . $month .'-' . $day;
	}
}
