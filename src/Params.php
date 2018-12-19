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

<<<<<<< HEAD
	public static function SafePost($param, $default = '')
	{
		if (isset($_POST[$param]))
		{
			$ret = $_POST[$param];
			if (is_array($ret) == false)
				$ret = trim($ret);

			return $ret;
		}
		return $default;
=======
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
>>>>>>> 9b76752be68a90ddf719a1e07706b16d64438e11
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
			throw new \Exception("Parameter " . $key . " required.");
		return $ret;
	}
}

