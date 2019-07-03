<?php

namespace minga\framework;

class PhpSession
{
	private static $sessionValues = null;

	public static function Destroy()
	{
		session_start();
		session_unset();
		session_destroy();
		// Crea una nueva
		session_start();
		session_regenerate_id();
		session_write_close();
		self::$sessionValues = array();
	}
	public static function SessionId()
	{
		return session_id();
	}
	public static function SetSessionValue($key, $value)
	{
		if (session_status() == PHP_SESSION_NONE)
			session_start();

		$_SESSION[$key] = $value;
		self::$sessionValues = $_SESSION;
		session_write_close();
	}

	private static function CheckPhpSessionStarted()
	{
		if (session_status() === PHP_SESSION_NONE)
		{
			session_start();
			if (self::$sessionValues == null)
				self::$sessionValues = $_SESSION;
			session_write_close();
		}
	}

	public static function GetSessionValue($key, $default = '')
	{
		self::CheckPhpSessionStarted();
		if (array_key_exists($key, self::$sessionValues))
			return self::$sessionValues[$key];
		else
			return $default;
	}

}
