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
}
