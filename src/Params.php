<?php

namespace minga\framework;

class Params
{
	//TODO: revisar y unificar los métodos de get.

	//Método usado en aacademica.
	public static function SafeGetCheckbox($param, $default = '0')
	{
		$value = self::SafeGet($param, $default);
		if ($value == 'on')
			$value = '1';
		return $value;
	}

	public static function SafeServer($param, $default = '')
	{
		if (isset($_SERVER[$param]) == false)
			return $default;

		$ret = $_SERVER[$param];
		if (is_array($ret) == false)
			$ret = trim($ret);

		return $ret;
	}

	public static function SafePost($param, $default = '')
	{
		if (isset($_POST[$param]) == false)
			return $default;

		$ret = $_POST[$param];
		if (is_array($ret) == false)
			$ret = trim($ret);

		return $ret;
	}

	//Método usado en aacademica.
	public static function SafeGet($param, $default = '')
	{
		$ret = $default;
		if (isset($_GET[$param]))
			$ret = $_GET[$param];
		else if (isset($_POST[$param]))
			$ret = $_POST[$param];
		if (is_array($ret) == false)
			$ret = trim($ret);
		return $ret;
	}

	//Método usado en mapas.
	public static function Get($key, $default = null)
	{
		if (isset($_GET[$key]))
		{
			$ret = $_GET[$key];
			if($ret === 'null')
				$ret = null;
		}
		else
		{
			if (isset($_POST[$key]))
			{
				$ret = $_POST[$key];
				if($ret === 'null')
					$ret = null;
			}
			else
				$ret = $default;
		}
		return $ret;
	}

	public static function CheckMandatoryValue($value, $key = '')
	{
		if ($value === null)
			throw new ErrorException('Parameter "' . $key . '" required.');
		return $value;
	}

	public static function GetMandatory($key)
	{
		$ret = self::Get($key, null);
		self::CheckMandatoryValue($ret, $key);
		return $ret;
	}

	public static function GetIntRangeMandatory($param, $min, $max)
	{
		$value = self::GetMandatory($param);
		$value = self::CheckParseIntValue($value);
		return self::processRange($value, $min, $max);
	}

	public static function GetIntMandatory($param)
	{
		$value = self::GetMandatory($param);
		return self::CheckParseIntValue($value);
	}

	public static function GetBoolMandatory($param)
	{
		$value = self::GetIntMandatory($param);
		return self::CheckParseIntValue($value) !== 0;
	}

	public static function GetBool(string $param, bool $default = false) : bool
	{
		return (bool)self::SafeGet($param, $default);
	}

	public static function GetInt($param, $default = null)
	{
		$value = self::Get($param, $default);
		if ($value === null || $value === '')
			return null;
		return self::CheckParseIntValue($value);
	}

	public static function GetIntArray($param, $default = array())
	{
		$value = self::Get($param, null);
		if ($value === null || $value === '')
			return $default;
		$arr = explode(',', $value);
		for($n = 0; $n < sizeof($arr); $n++)
			$arr[$n] = self::CheckParseIntValue($arr[$n]);
	
		return $arr;
	}

	private static function processRange($value, $min, $max)
	{
		if ($value < $min || $value > $max)
			throw new ErrorException('Parameter value of "' . $value . '" is out of range.');
		return $value;
	}

	public static function FromPath($position, $default = null)
	{
		$uri = Request::GetRequestURI(true);
		$parts = explode('/', $uri);
		if (sizeof($parts) <= $position)
			return $default;
		else
			return $parts[$position];
	}

	public static function CheckParseIntValue($value)
	{
		$i = (int)$value;
		if ((string)$i !== (string)$value)
			throw new ErrorException('Parameter value of "' . $value . '" is invalid.');
		return $i;
	}

	public static function GetJsonMandatory($param, $assoc = false)
	{
		$value = self::GetMandatory($param);
		return json_decode($value, $assoc);
	}

	public static function GetJson($param, $assoc = false)
	{
		$value = Params::Get($param);
		if ($value === null)
			return null;
		return json_decode($value, $assoc);
	}
}

