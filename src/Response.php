<?php

namespace minga\framework;

class Response
{
	private static bool $isJson = false;

	public static function SetJson() : void
	{
		Profiling::$IsJson = true;
		self::$isJson = true;
	}

	public static function IsJson() : bool
	{
		return self::$isJson;
	}

	public static function RedirectKeepingParams(string $url, int $status = 302) : void
	{
		$ret = $url;
		$params = Request::GetQueryString();
		if ($params)
		{
			if (Str::Contains($ret, "?") == false)
				$ret .= "?";
			else
				$ret .= "&";
			$ret .= $params;
		}
		self::Redirect($ret, $status);
	}

	public static function Redirect(string $url, int $status = 302) : void
	{
		header('Location: ' . $url, true, $status);
		exit();
	}

	public static function PermanentRedirect(string $url) : void
	{
		header('Location: ' . $url, true, 301);
		exit();
	}
}
