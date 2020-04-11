<?php

namespace minga\framework;

class Request
{
	private static $isGoogle = null;

	public static function IsGoogle()
	{
		if (self::$isGoogle == null)
		{
			$agent = Params::SafeServer('HTTP_USER_AGENT', 'null');
			self::$isGoogle = Str::Contains($agent, "Googlebot");
		}
		return self::$isGoogle;
	}

	public static function GetSecondUriPart()
	{
		$uri = self::GetRequestURI(true);
		$parts = explode('/', $uri);
		array_shift($parts);
		if (count($parts) < 2)
			return null;
		return $parts[1];
	}

	public static function GetThirdUriPart()
	{
		$uri = self::GetRequestURI(true);
		$parts = explode('/', $uri);
		array_shift($parts);
		if (count($parts) < 3)
			return null;
		return $parts[2];
	}

	public static function GetRequestURI($noParameters = false)
	{
		if ($noParameters)
			return explode('?', $_SERVER['REQUEST_URI'], 2)[0];
		return $_SERVER['REQUEST_URI'];
	}
}
