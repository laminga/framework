<?php

namespace minga\framework;

use minga\framework\settings\Settings;

class Context
{
	private static $settings = null;
	private static $calls = null;
	private static $paths = null;

	public static function Settings()
	{
		if(self::$settings === null)
			self::$settings = new Settings();

		return self::$settings;
	}

	public static function Calls()
	{
		if(self::$calls === null)
			throw new \Exception('Framework context Calls must be initialized.');

		return self::$calls;
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
}
