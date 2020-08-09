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

	public static function Referer()
	{
		if (!empty($_SERVER['HTTP_REFERER'])) {
		  return $_SERVER['HTTP_REFERER'];
		} else {
			return '';
		}
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
	public static function RequestURIStartsWith($arg1, $arg2 = null, $arg3 = null, $arg4 = null)
	{
		$uri = self::GetRequestURI();
		if (Str::StartsWith($uri, $arg1)) return true;
		if (Str::StartsWith($uri, $arg2)) return true;
		if (Str::StartsWith($uri, $arg3)) return true;
		if (Str::StartsWith($uri, $arg4)) return true;
		return false;
	}
	public static function GetQueryString()
	{
		return Params::SafeServer('QUERY_STRING');
	}
	public static function GetRequestURI($noParameters = false)
	{
		if ($noParameters)
		{
			$parts = explode('?', Params::SafeServer('REQUEST_URI'), 2);
			return $parts[0];
		}
		return Params::SafeServer('REQUEST_URI');
	}
}
