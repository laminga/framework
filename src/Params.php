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
		if (array_key_exists($param, $_GET))
			$ret = $_GET[$param];
		else if (array_key_exists($param, $_POST))
			$ret = $_POST[$param];
		if (is_array($ret) == false)
			$ret = trim($ret);
		return $ret;
	}

	//Método usado en mapas.
	public static function Get($key, $default = null)
	{
		if (array_key_exists($key, $_GET))
		{
			$ret = $_GET[$key];
			if($ret === 'null')
				$ret = null;
		}
		else
		{
			if (array_key_exists($key, $_POST))
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

	public static function GetMandatory($key)
	{
		$ret = self::Get($key, null);
		if ($ret === null)
			throw new ErrorException("Parameter " . $key . " required.");
		return $ret;
	}

	public static function GetIntRangeMandatory($param, $min, $max)
	{
		$value = self::GetMandatory($param);
		$value = self::processIntValue($value);
		return self::processRange($value, $min, $max);
	}

	public static function GetIntMandatory($param)
	{
		$value = self::GetMandatory($param);
		return self::processIntValue($value);
	}


	public static function GetBoolMandatory($param)
	{
		$value = self::GetIntMandatory($param);
		return self::processIntValue($value) !== 0;
	}

	public static function GetBool($param, $default = false)
	{
		$value = self::Get($param, ($default ? 1 : 0));
		if ($value === null)
			return null;
		return self::processIntValue($value) !== 0;
	}

	public static function GetInt($param, $default = null)
	{
		$value = self::Get($param, $default);
		if ($value === null)
			return null;
		return self::processIntValue($value);
	}

	private static function processRange($value, $min, $max)
	{
		if ($value < $min || $value > $max)
		{
			throw new ErrorException('Parameter value of ' . $value . ' is out of range.');
		}
		else
			return $value;
	}

	private static function processIntValue($value)
	{
		$i = (int)$value;
		if ((string)$i !== (string)$value)
		{
			throw new ErrorException('Parameter value of ' . $value . ' is invalid.');
		}
		else
			return $i;
	}

	public static function GetJsonMandatory($param)
	{
		$value = self::GetMandatory($param);
		return json_decode($value);
	}

	public static function GetJson($param)
	{
		$value = Params::Get($param);
		if ($value === null)
			return null;
		else
			return json_decode($value);
	}
}

