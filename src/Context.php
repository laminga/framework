<?php

namespace minga\framework;

use minga\framework\settings\Settings;

class Context
{
	private static Settings $settings;
	private static $calls = null;

	private static AppPaths $paths;

	public static function Settings() : Settings
	{
		if(isset(self::$settings) == false)
			self::$settings = new Settings();

		return self::$settings;
	}

	public static function Calls()
	{
		if(isset(self::$calls) == false)
			throw new ErrorException('Las llamadas de contexto del framework "Calls" deber ser inicializadas.');

		return self::$calls;
	}

	public static function CurrentUrl() : string
	{
		$ret = 'http://';
		if(Request::IsSecure())
			$ret = 'https://';

		return $ret . Params::SafeServer('HTTP_HOST') . Params::SafeServer('REQUEST_URI');
	}

	public static function InjectSettings($settings) : void
	{
		self::$settings = $settings;
	}

	public static function InjectCallbacks($calls) : void
	{
		self::$calls = $calls;
	}

	public static function Paths() : AppPaths
	{
		if(isset(self::$paths) == false)
			self::$paths = new AppPaths();

		return self::$paths;
	}

	public static function LoggedUser() : string
	{
		return PhpSession::GetSessionValue('user');
	}

	public static function EndRequest() : void
	{
		self::Calls()->EndRequest();
	}

	public static function ExtraHitsLabels() : array
	{
		return self::Calls()->ExtraHitsLabels();
	}

	public static function ExtraHits() : array
	{
		return self::Calls()->ExtraHits();
	}

	public static function Trans(string $str, array $parameters = [], ?string $domain = null, ?string $locale = null) : string
	{
		return self::Calls()->Trans($str, $parameters, $domain, $locale);
	}
}
