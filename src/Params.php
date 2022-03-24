<?php

namespace minga\framework;

class Params
{
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
			throw new ErrorException(Context::Trans('Parámetro "') . $key . Context::Trans('" requerido.'));
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
		return self::ProcessRange($value, $min, $max);
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

	private static function ProcessRange($value, $min, $max)
	{
		if ($value < $min || $value > $max)
			throw new ErrorException(Context::Trans('El valor del parámetro "') . $value . Context::Trans('" está fuera de rango.'));
		return $value;
	}

	public static function GetUploadedImageMemory(string $field, int $maxFileSize = -1)
	{
		$file = self::GetUploadedImage($field, $maxFileSize);
		return IO::ReadAllBytes($file);
	}

	public static function GetUploadedFileMemory(string $field, array $validExtensions, int $maxFileSize = -1)
	{
		$file = self::GetUploadedFile($field, $validExtensions, $maxFileSize);
		return IO::ReadAllBytes($file);
	}

	public static function GetUploadedImage(string $field, int $maxFileSize = -1) : string
	{
		return self::GetUploadedFile($field, Extensions::Images, $maxFileSize);
	}

	public static function GetUploadedFile(string $field, array $validExtensions, int $maxFileSize = -1) : string
	{
		//No hay archivo...
		if (isset($_FILES[$field]) == false || $_FILES[$field]['size'] == 0)
			return '';

		if ($_FILES[$field]['error'] != 0)
			throw new ErrorException(Context::Trans('Error al subir el archivo.'));

		if ($maxFileSize != -1 && $_FILES[$field]['size'] > $maxFileSize)
			throw new ErrorException(Context::Trans('El archivo excede el tamaño máximo.'));

		$ext = Extensions::GetExtensionFromMimeType($_FILES[$field]['type']);
		if(in_array($ext, $validExtensions) == false)
			throw new ErrorException(Context::Trans('El formato del archivo no es válido.'));

		$tmpFile = IO::GetTempFilename() . '.' . $ext;

		if (move_uploaded_file($_FILES[$field]['tmp_name'], $tmpFile) == false)
			throw new ErrorException(Context::Trans('Error al guardar el archivo.'));

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
			throw new ErrorException(Context::Trans('El valor del parámetro "') . $value . Context::Trans('" no es válido.'));
		return $i;
	}

	public static function CheckParseMonthValue($value)
	{
		if (strlen($value) !== 7 || substr($value, 4, 1) !== '-')
			throw new ErrorException(Context::Trans('El valor del parámetro "') . $value . Context::Trans('" no es válido.'));
		$y = self::CheckParseIntValue(substr($value, 0, 4));
		$m = self::CheckParseIntValue(ltrim(substr($value, 5, 2), '0'));
		if ($y < 2000 || $y > 3000 || $m < 1 || $m > 12)
			throw new ErrorException(Context::Trans('El valor del parámetro "') . $value . Context::Trans('" no es válido.'));
		return $value;
	}

	public static function GetJsonMandatory($param, bool $assoc = false)
	{
		$value = self::GetMandatory($param);
		return json_decode($value, $assoc);
	}

	public static function GetJson($param, bool $assoc = false)
	{
		$value = Params::Get($param);
		if ($value === null)
			return null;
		return json_decode($value, $assoc);
	}
}

