<?php

namespace minga\framework;

class Date
{
	public static function SecondsToMidnight() : int
	{
		$midnight = new \DateTime('tomorrow 00:00:00');
		$now = new \DateTime('now');

		$interval = $midnight->diff($now);
		return $interval->h * 60 * 60
			+ $interval->i * 60
			+ $interval->s
			// Un minutito más...
			+ 60;
	}

	public static function GetLogDayFolder() : string
	{
		return date("d");
	}

	public static function GetLogMonthFolderYearMonth($year, $month) : string
	{
		return self::doGetLogMonthFolder($year, $month);
	}

	public static function GetLogMonthFolder(int $offset = 0) : string
	{
		$now = self::DateTimeArNow();
		return self::doGetLogMonthFolder(self::DateTimeGetYear($now), self::DateTimeGetMonth($now), $offset);
	}

	private static function doGetLogMonthFolder($year, $month, $offset = 0) : string
	{
		$time = mktime(0, 0, 0, $month + $offset, 1, $year);
		return date("Y-m", $time);
	}

	public static function NowGMT(int $offset) : int
	{
		if ($offset < -12 || $offset > 12)
			throw new ErrorException(Context::Trans('Desplazamiento de tiempo fuera de rango: {offset}', ['{offset}' => $offset]));
		return self::UniversalNow() + 60 * 60 * $offset;
	}

	public static function UniversalNow() : int
	{
		$virtual = PhpSession::GetSessionValue('now');
		if ($virtual == '')
			return time();

		return (int)$virtual;
	}

	public static function ChangeUniversalNow(int $time) : void
	{
		PhpSession::SetSessionValue('now', $time);
	}

	public static function ArNow() : int
	{
		return self::NowGMT(-3);
	}

	public static function FormattedArDate() : string
	{
		return self::FormattedDateOnly(self::ArNow());
	}

	public static function FormattedArNow() : string
	{
		return self::FormattedDate(self::ArNow());
	}

	//Con milisegundos
	public static function FormattedArNowMs() : string
	{
		$parts = explode(' ', microtime());
		$mt = sprintf("%03d", round((float)$parts[0] * 1000));
		return self::FormattedDate(self::ArNow()) . '.' . $mt;
	}

	public static function FormatDateDMY(string $str) : string
	{
		if ($str == "")
			return "-";
		return substr($str, 8, 2) . "/" . substr($str, 5, 2) . "/" . substr($str, 2, 2);
	}

	public static function FormattedDateOnly(int $date) : string
	{
		return date("Y-m-d", $date);
	}

	public static function FormatDateYYMD(string $str) : string
	{
		if ($str == "")
			return "-";
		return substr($str, 0, 4) . "-" . substr($str, 5, 2) . "-" . substr($str, 8, 2);
	}

	public static function DateToDDMMYYYY($date) : string
	{
		return date("d/m/Y", $date);
	}

	public static function FormattedDate($date) : string
	{
		return date("Y-m-d@H.i.s", $date);
	}

	public static function DbDate(int $date) : string
	{
		return date("Y-m-d H:i:s", $date);
	}

	public static function ConvertFormattedDateDDMMYYYYHHMM(string $date) : string
	{
		if ($date == "")
			return "";
		return substr($date, 8, 2) . '/' . substr($date, 5, 2) . '/'
			. substr($date, 0, 4) . ' ' . substr($date, 11, 2) . ':' . substr($date, 14, 2);
	}

	public static function ConvertFormattedDateDDMMYYYY(string $date) : string
	{
		if ($date == "")
			return "";
		return substr($date, 8, 2) . '/' . substr($date, 5, 2)
			. '/' . substr($date, 0, 4);
	}

	public static function ConvertFromDDMMYYYYToYYYYMMDD(string $date) : string
	{
		if ($date == "")
			return "";
		if (strlen(trim($date)) != 10)
			$date = self::AddZerosInDate($date);
		return substr($date, 6, 4) . '-' . substr($date, 3, 2) . '-' . substr($date, 0, 2);
	}

	public static function AbsoluteMonth($date) : int
	{
		return self::DateTimeGetYear($date) * 12 + self::DateTimeGetMonth($date) - 1;
	}

	public static function DateTimeGetMonth($date) : int
	{
		return (int)$date->format('m');
	}

	public static function DateTimeGetYear($date) : int
	{
		return (int)$date->format('Y');
	}

	public static function ParseTime(string $span) : int
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

	public static function FormatTime(int $minutes) : string
	{
		$mod = $minutes % 60;
		$ret = floor($minutes / 60);
		$ret .= ":" . ($mod <= 9 ? '0' : '') . $mod;
		return $ret;
	}

	public static function ParseSpan(string $span) : int
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

	public static function FormatSpan(int $minutes) : string
	{
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

	public static function DbArNow() : string
	{
		return self::DbDate(self::ArNow());
	}

	public static function DateTimeArNow()
	{
		$date = new \DateTime();
		$date->setTimestamp(self::ArNow());
		return $date;
	}

	public static function DaysDiff(\DateTime $date1, \DateTime $date2) : int
	{
		$interval = date_diff($date1, $date2);
		return (int)$interval->format('%a');
	}

	public static function DateTimeNow() : \DateTime
	{
		$date = new \DateTime();
		$date->setTimestamp(time());
		return $date;
	}

	public static function DateTimeToday() : \DateTime
	{
		return date_create(self::Today());
	}

	public static function Today() : string
	{
		return date("Y-m-d");
	}

	public static function CurrentDay() : int
	{
		return (int)(date("j"));
	}

	public static function CurrentMonth() : int
	{
		return (int)date("m");
	}

	public static function CurrentYear() : string
	{
		return self::GetYearFromDay(date("Y-m-d"));
	}

	public static function GetYearMonthFromDay(string $day) : string
	{
		// formato: 2015-02-29
		return substr($day, 0, 7);
	}

	public static function GetYearFromDay(string $day) : string
	{
		// formato: 2015-02-29
		return substr($day, 0, 4);
	}

	public static function GetMonthFromDay(string $day) : string
	{
		// formato: 2015-02-29
		return substr($day, 5, 2);
	}

	public static function FormatDateText(string $year, string $month, string $day) : string
	{
		$ret = $day . " de " . Str::ToLower(self::MonthToString((int)$month));
		if ($year != "")
			$ret .= " de " . $year;
		return $ret;
	}

	public static function WeekDayToString(int $day) : string
	{
		switch((int)$day)
		{
			case 1:
				return Context::Trans('Lunes');
			case 2:
				return Context::Trans('Martes');
			case 3:
				return Context::Trans('Miércoles');
			case 4:
				return Context::Trans('Jueves');
			case 5:
				return Context::Trans('Viernes');
			case 6:
				return Context::Trans('Sábado');
			case 7:
				return Context::Trans('Domingo');
			default:
				return '';
		}
	}

	public static function MonthToString(int $month) : string
	{
		switch($month)
		{
			case 1:
				return Context::Trans('Enero');
			case 2:
				return Context::Trans('Febrero');
			case 3:
				return Context::Trans('Marzo');
			case 4:
				return Context::Trans('Abril');
			case 5:
				return Context::Trans('Mayo');
			case 6:
				return Context::Trans('Junio');
			case 7:
				return Context::Trans('Julio');
			case 8:
				return Context::Trans('Agosto');
			case 9:
				return Context::Trans('Septiembre');
			case 10:
				return Context::Trans('Octubre');
			case 11:
				return Context::Trans('Noviembre');
			case 12:
				return Context::Trans('Diciembre');
			default:
				return '';
		}
	}

	public static function DateStringToDateTime(string $date) : \DateTime
	{
		$ret = \DateTime::createFromFormat("Ymd", $date);
		if($ret === false)
			throw new ErrorException(Context::Trans('Formato no válido'));
		return $ret;
	}

	public static function FormattedDateToDateTime(string $date) : \DateTime
	{
		$ret = \DateTime::createFromFormat("Y-m-d@H.i.s", $date);
		if($ret === false)
			throw new ErrorException(Context::Trans('Formato no válido'));
		return $ret;
	}

	/*
	 * Ver DateTimeNotPast
	 * $date en formato de FormattedDate().
	 *
	 */
	public static function DateNotPast(string $date, int $days) : bool
	{
		if($date == '')
			return false;

		if($days <= 0)
			throw new ErrorException(Context::Trans('Días debe ser un entero positivo'));

		return self::DateTimeNotPast(self::FormattedDateToDateTime($date), $days);
	}

	/*
	 * Compara una fecha ($date) sumándole días ($days)
	 * con "now".
	 * Si esa fecha + días es mayor a now devuelve true,
	 * es decir, no está pasada la fecha de vencimiento,
	 * si no, false.
	 *
	 * $date en formato de FormattedDate().
	 *
	 */
	public static function DateTimeNotPast(\DateTime $dt, int $days) : bool
	{
		if($days <= 0)
			throw new ErrorException(Context::Trans('Días debe ser un entero positivo'));

		$dt->add(new \DateInterval('P' . $days . 'D'));
		$now = new \DateTime('now');

		return $dt > $now;
	}

	public static function TryParseDate(string $date, ?string &$day, ?string &$month, ?string &$year) : bool
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
		if (checkdate($parts[1], $parts[0], $parts[2]) == false)
			return false;
		$day = (string)$parts[0];
		$month = (string)$parts[1];
		$year = (string)$parts[2];
		return true;
	}

	public static function ParseDate(string $date) : string
	{
		$day = null;
		$month = null;
		$year = null;
		$bret = self::TryParseDate($date, $day, $month, $year);
		if ($bret == false)
			throw new ErrorException(Context::Trans('Fecha no válida.'));
		return $year . '-' . $month . '-' . $day;
	}
}
