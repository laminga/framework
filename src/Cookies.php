<?php

namespace minga\framework;

class Cookies
{

	public static function SetCookie($name, $value, $expireDays = 30)
	{
		$expire = time() + 60 * 60 * 24 * $expireDays;

		//Si tiene https no importa el entorno, es segura.
		$scheme = parse_url(Context::Settings()->GetMainServerPublicUrl(), PHP_URL_SCHEME);
		$secure = ($scheme == "https");

		$host = parse_url(Context::Settings()->GetMainServerPublicUrl(), PHP_URL_HOST);

		$ret = setcookie($name, $value, $expire, '/', $host, $secure, true);

		if($ret === false)
			Log::HandleSilentException(new ErrorException('SetCookie'));
	}

	public static function RenewCookie($name, $expireDays = 30)
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

	public static function DeleteCookie($name)
	{
		self::RenewCookie($name, -365);
		unset($_COOKIE[$name]);
	}

}
