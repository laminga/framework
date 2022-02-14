<?php

namespace minga\framework;

class Request
{
	/** @var bool */
	private static $isGoogle = null;

	public static function IsGoogle() : bool
	{
		if (self::$isGoogle == null)
		{
			$agent = Params::SafeServer('HTTP_USER_AGENT', 'null');
			self::$isGoogle = Str::Contains($agent, "Googlebot");
		}
		return self::$isGoogle;
	}

	public static function Referer() : string
	{
		return Params::SafeServer('HTTP_REFERER');
	}

	public static function Host() : string
	{
		return Params::SafeServer('HTTP_HOST');
	}

	public static function Subdomain() : string
	{
		$host = self::Host();
		$parts = explode('.', $host);
		return $parts[0];
	}

	public static function GetSecondUriPart() : ?string
	{
		$uri = self::GetRequestURI(true);
		$parts = explode('/', $uri);
		array_shift($parts);
		if (count($parts) < 2)
			return null;
		return $parts[1];
	}

	public static function GetThirdUriPart() : ?string
	{
		$uri = self::GetRequestURI(true);
		$parts = explode('/', $uri);
		array_shift($parts);
		if (count($parts) < 3)
			return null;
		return $parts[2];
	}

	public static function RequestURIStartsWith(?string $arg1, ?string $arg2 = null, ?string $arg3 = null, ?string $arg4 = null) : bool
	{
		$uri = self::GetRequestURI();
		return Str::StartsWith($uri, $arg1)
			|| Str::StartsWith($uri, $arg2)
			|| Str::StartsWith($uri, $arg3)
			|| Str::StartsWith($uri, $arg4);
	}

	public static function GetQueryString() : string
	{
		return Params::SafeServer('QUERY_STRING');
	}

	public static function GetRequestURI(bool $noParameters = false) : string
	{
		if ($noParameters)
		{
			$parts = explode('?', Params::SafeServer('REQUEST_URI'), 2);
			return $parts[0];
		}
		return Params::SafeServer('REQUEST_URI');
	}
}
