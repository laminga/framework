<?php

namespace minga\framework;

class PhpSession
{
	private static ?array $sessionValues = null;

	public static function Destroy() : void
	{
		if (Context::Settings()->allowPHPsession)
		{
			self::SessionStart();
			session_unset();
			session_destroy();
			// Crea una nueva
			self::SessionStart();
			session_regenerate_id();
			session_write_close();
		}
		self::$sessionValues = null;
	}

	public static function SessionId() : string
	{
		if (Context::Settings()->allowPHPsession)
			return session_id();
		return '';
	}

	public static function SetSessionValue(string $key, $value) : void
	{
		if (Context::Settings()->allowPHPsession)
		{
			if (session_status() == PHP_SESSION_NONE)
				self::SessionStart();

			$_SESSION[$key] = $value;
			session_write_close();
			self::$sessionValues = $_SESSION;
		}
		else
		{
			if (is_array(self::$sessionValues))
				self::$sessionValues[$key] = $value;
			else
				self::$sessionValues = [$key => $value];
		}
	}

	private static function SessionExists() : bool
	{
		if (Context::Settings()->allowPHPsession == false)
			return false;

		return isset($_SESSION) != false || session_status() !== PHP_SESSION_NONE;
	}

	public static function CheckPhpSessionStarted($readOperation = false) : void
	{
		if (Context::Settings()->allowPHPsession)
		{
			if (isset($_SESSION) == false
				&& session_status() === PHP_SESSION_NONE)
			{
				$hasSession = isset($_COOKIE["PHPSESSID"]);
				if (!$readOperation || $hasSession)
				{
					self::SessionStart();
					if (self::$sessionValues == null)
						self::$sessionValues = $_SESSION;
					session_write_close();
				}
			}
		}
		else if (self::$sessionValues == null)
			self::$sessionValues = [];
	}

	public static function GetSessionValue(string $key, $default = '')
	{
		self::CheckPhpSessionStarted(true);

		if (self::$sessionValues == null && self::SessionExists())
			self::$sessionValues = $_SESSION;

		if (self::$sessionValues != null && isset(self::$sessionValues[$key]))
			return self::$sessionValues[$key];

		return $default;
	}

	private static function SessionStart() : bool
	{
		if (ini_get('session.use_cookies') && isset($_COOKIE['PHPSESSID'])
			&& preg_match('/^[a-zA-Z0-9,\-]{22,40}$/', $_COOKIE['PHPSESSID']) == false)
		{
			//Sale con página vacía. Este error es solo por manipulación
			//intencional y ningún redirect o unset lo resuelve.
			exit();
		}

		if (Context::Settings()->allowCrossSiteSessionCookie)
		{
			session_set_cookie_params(["SameSite" => "none"]);
			session_set_cookie_params(["Secure" => true]);
		}
		else
			session_set_cookie_params(["Secure" => Request::IsSecure()]);
		return session_start();
	}
}
