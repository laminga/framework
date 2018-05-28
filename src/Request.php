<?php

namespace minga\framework;

class Request
{
	private static $isGoogle = null;

	public static function IsGoogle()
	{
		if (self::$isGoogle == null)
		{
			if (array_key_exists('HTTP_USER_AGENT', $_SERVER))
				$agent = $_SERVER['HTTP_USER_AGENT'];
			else
				$agent = 'null';
			self::$isGoogle = Str::Contains($agent, "Googlebot");
		}
		return self::$isGoogle;
	}
}
