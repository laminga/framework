<?php

namespace minga\framework;

class Cookies
{
	public static function SetCookie($name, $value, $expireDays = 30) : void
	{
		$expire = time() + 60 * 60 * 24 * $expireDays;

		//Si tiene https no importa el entorno, es segura.
		$secure = self::IsSecure();
		$secure = true;
		$host = $_SERVER['HTTP_HOST'];
		if ($host == false)
			$host = parse_url(Context::Settings()->GetPublicUrl(), PHP_URL_HOST);

		$ret = setcookie($name, $value, $expire, '/', $host, $secure, true);

		if($ret === false)
			Log::HandleSilentException(new ErrorException('SetCookie'));
	}

	public static function IsSecure()
	{
		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
			return true;
		elseif (empty($_SERVER['HTTP_X_FORWARDED_PROTO']) == false
			&& $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || empty($_SERVER['HTTP_X_FORWARDED_SSL']) == false
			&& $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on')
		{
			return true;
		}
		return false;
	}

	public static function RenewCookie($name, $expireDays = 30) : void
	{
		$cookie = self::GetCookie($name);
		if($cookie != '')
			self::SetCookie($name, $cookie, $expireDays);
	}

	public static function GetCookie($name)
	{
		if(isset($_COOKIE[$name]))
			return $_COOKIE[$name];

		return '';
	}

	public static function DeleteCookie($name) : void
	{
		self::RenewCookie($name, -365);
		unset($_COOKIE[$name]);
	}
}
