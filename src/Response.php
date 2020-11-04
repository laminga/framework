<?php

namespace minga\framework;

class Response
{
	private static $isJson = false;

	public static function SetJson() : void
	{
		Profiling::$IsJson = true;
		self::$isJson = true;
	}

	public static function IsJson() : bool
	{
		return self::$isJson;
	}

	public static function Redirect(string $url) : void
	{
		header('Location: ' . $url);
		exit();
	}
}
