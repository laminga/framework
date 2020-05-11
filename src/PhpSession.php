<?php

namespace minga\framework;

class PhpSession
{
	private static $sessionValues = null;

	public static function Destroy()
	{
		if (Context::Settings()->allowPHPsession)
		{
			session_start();
			session_unset();
			session_destroy();
			// Crea una nueva
			session_start();
			session_regenerate_id();
			session_write_close();
		}
		self::$sessionValues = [];
	}

	public static function SessionId()
	{
		if (Context::Settings()->allowPHPsession)
		{
			return session_id();
		}
		else 
		{
			return null;
		}
	}

	public static function SetSessionValue($key, $value)
	{
		if (Context::Settings()->allowPHPsession)
		{
			if (session_status() == PHP_SESSION_NONE)
				session_start();
			
			$_SESSION[$key] = $value;
			session_write_close();
			self::$sessionValues = $_SESSION;
		}
		else
		{
			if (is_array(self::$sessionValues))
				self::$sessionValues[$key] = $value;
			else
				self::$sessionValues = [ $key => $value ];
		}
	}

	private static function CheckPhpSessionStarted()
	{
		if (Context::Settings()->allowPHPsession)
		{
			if (isset($_SESSION) == false
				&& session_status() === PHP_SESSION_NONE)
			{
				session_start();
				if (self::$sessionValues == null)
					self::$sessionValues = $_SESSION;
				session_write_close();
			}
		}
		else 
		{
			if (self::$sessionValues == null)
					self::$sessionValues = [];
		}
	}

	public static function GetSessionValue($key, $default = '')
	{
		self::CheckPhpSessionStarted();
		if (array_key_exists($key, self::$sessionValues))
			return self::$sessionValues[$key];
		return $default;
	}

}
