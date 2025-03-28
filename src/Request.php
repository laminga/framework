<?php

namespace minga\framework;

class Request
{
	private static bool $isGoogle;

	public static function IsSecure() : bool
	{
		return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
			|| (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
	}

	public static function IsGoogle() : bool
	{
		if (isset(self::$isGoogle) == false)
		{
			$agent = self::UserAgent();
			self::$isGoogle = Str::Contains($agent, "Googlebot");
		}
		return self::$isGoogle;
	}

	public static function Protocol() : string
	{
		if (
			isset($_SERVER['HTTPS'])
			&& $_SERVER['HTTPS'] !== 'off'
			&& $_SERVER['HTTPS'] !== '')
	  	{
			return 'https';
		}
		return 'http';
	}

	public static function IP($default = '') : string
	{
		$addr = Params::SafeServer('HTTP_X_FORWARDED_FOR', $default);
		if ($addr !== $default)
		{	// Si hay varias, retiene solo la primera
			$parts = explode(',', $addr);
			if (count($parts) > 1)
				$addr = $parts[0];
		}
		if ($addr === $default)
			$addr = Params::SafeServer('REMOTE_ADDR', $default);
		return $addr;
	}

	public static function UserAgent(): string
	{
		return Params::SafeServer('HTTP_USER_AGENT', '');
	}

	public static function Referer() : string
	{
		return Params::SafeServer('HTTP_REFERER');
	}

	public static function UserAgent(): string
	{
		return Params::SafeServer('HTTP_USER_AGENT', '');
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

	public static function GetLastUriPart() : ?string
	{
		$uri = self::GetRequestURI(true);
		$parts = explode('/', $uri);
		if (count($parts) < 1)
			return null;
		return $parts[count($parts) - 1];
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

	public static function IsInternal() : bool
	{
		return Params::SafeServer('HTTP_INTERNAL') === "1";
	}

	public static function GetHttpRange() : ?string
	{
		return Params::SafeServer('HTTP_RANGE', null);
	}

	public static function GetRequestURI(bool $noParameters = false) : string
	{
		$uri = Params::SafeServer('REQUEST_URI');
		if ($noParameters)
		{
			$parts = explode('?', $uri, 2);
			$uri = $parts[0];
		}
		return Str::RemoveEnding($uri, '/');
	}
}
