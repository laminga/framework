<?php

namespace minga\framework;

use minga\framework\settings\Settings;

class Context
{
	/** @var Settings */
	private static $settings = null;
	private static $calls = null;
	private static $paths = null;

	public static function Settings() : Settings
	{
		if(self::$settings === null)
			self::$settings = new Settings();

		return self::$settings;
	}

	public static function Calls()
	{
		if(self::$calls === null)
			throw new ErrorException('Framework context Calls must be initialized.');

		return self::$calls;
	}

	public static function CurrentUrl()
	{
		$ret = 'http://';
		if(Params::SafeServer('HTTPS') == 'on')
			$ret = 'https://';

		return $ret . Params::SafeServer('HTTP_HOST') . Params::SafeServer('REQUEST_URI');
	}

	public static function InjectSettings($settings)
	{
		self::$settings = $settings;
	}

	public static function InjectCallbacks($calls)
	{
		self::$calls= $calls;
	}

	public static function Paths()
	{
		if(self::$paths === null)
			self::$paths = new AppPaths();

		return self::$paths;
	}

	public static function LoggedUser()
	{
		return PhpSession::GetSessionValue('user');
	}

	public static function EndRequest()
	{
		self::Calls()->EndRequest();
	}

	public static function ExtraHitsLabels()
	{
		return self::Calls()->ExtraHitsLabels();
	}

	public static function ExtraHits()
	{
		return self::Calls()->ExtraHits();
	}
}
