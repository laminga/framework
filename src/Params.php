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

	public static function Exists($key)
	{
		return isset($_GET[$key]);
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

	public static function GetMonthMandatory($param)
	{
		$value = self::GetMandatory($param);
		return self::CheckParseMonthValue($value);
	}

	public static function GetBoolMandatory(string $param) : bool
	{
		$value = self::GetMandatory($param);
		if(strtolower($value) == 'false')
			return false;
		if(strtolower($value) == 'true')
			return true;
		return self::CheckParseIntValue($value) !== 0;
	}

	public static function GetBool(string $param, bool $default = false) : bool
	{
		return (bool)self::SafeGet($param, $default);
	}

	public static function GetInt($param, $default = null)
	{
		$value = self::Get($param, $default);
		if ($value === null || $value === '' || $value === $default)
			return $default;
		return self::CheckParseIntValue($value);
	}

	public static function GetMonth($param, $default = null)
	{
		$value = self::Get($param, $default);
		if ($value === null || $value === '')
			return null;
		return self::CheckParseMonthValue($value);
	}

	public static function GetIntArray($param, $default = [])
	{
		$value = self::Get($param, null);
		if ($value === null || $value === '' || $value === '[]')
			return $default;
		if (Str::StartsWith($value, "[") && Str::EndsWith($value, "]"))
			$value = substr($value, 1, strlen($value) - 2);
		$arr = explode(',', $value);
		for($n = 0; $n < count($arr); $n++)
			$arr[$n] = self::CheckParseIntValue($arr[$n]);

		return $arr;
	}

	private static function processRange($value, $min, $max)
	{
		if ($value < $min || $value > $max)
			throw new ErrorException('Parameter value of "' . $value . '" is out of range.');
		return $value;
	}

	public static function GetUploadedImageMemory($param, $maxFileSize = -1)
	{
		$file = self::GetUploadedImage($param, $maxFileSize);
		return IO::ReadAllBytes($file);
	}

	public static function GetUploadedFileMemory($param, $maxFileSize = -1, $validFileTypes = [])
	{
		$file = self::GetUploadedFile($param, $maxFileSize, $validFileTypes);
		return IO::ReadAllBytes($file);
	}

	public static function GetUploadedImage($param, $maxFileSize = -1)
	{
		return self::GetUploadedFile($param, $maxFileSize, [
			'jpg' => 'image/jpeg',
			'png' => 'image/png',
		]);
	}

	public static function GetUploadedFile($param, $maxFileSize = -1, $validFileTypes = [])
	{
		// You should also check filesize here.
		if ($maxFileSize === -1 || $_FILES[$param]['size'] > $maxFileSize) {
			throw new \RuntimeException('Exceeded filesize limit.');
		}
		// Check MIME Type by yourself.
		$finfo = new \finfo(FILEINFO_MIME_TYPE);
		if (count($validFileTypes) == 0 || !array_search(
			$finfo->file($_FILES[$param]['tmp_name']),
			$validFileTypes, true)) {
			throw new \RuntimeException('Invalid file format.');
		}
		$tmpFile = IO::GetTempFilename();
		// On this example, obtain safe unique name from its binary data.
		if (!move_uploaded_file($_FILES[$param]['tmp_name'], $tmpFile)) {
			throw new \RuntimeException('Failed to move uploaded file.');
		}
		return $tmpFile;
	}

	public static function FromPath($position, $default = null)
	{
		$uri = Request::GetRequestURI(true);
		$parts = explode('/', $uri);
		if (count($parts) <= $position)
			return $default;

			return $parts[$position];
	}

	public static function CheckParseIntValue($value)
	{
		$i = (int)$value;
		if ((string)$i !== (string)$value)
			throw new ErrorException('Parameter value of "' . $value . '" is invalid.');
		return $i;
	}

	public static function CheckParseMonthValue($value)
	{
		if (strlen($value) !== 7 || substr($value, 4, 1) !== '-')
			throw new ErrorException('Parameter value of "' . $value . '" is invalid.');
		$y = self::CheckParseIntValue(substr($value, 0, 4));
		$m = self::CheckParseIntValue(ltrim(substr($value, 5, 2), '0'));
		if ($y < 2000 || $y > 3000 || $m < 1 || $m > 12)
			throw new ErrorException('Parameter value of "' . $value . '" is invalid.');
		return $value;
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

