<?php

namespace minga\framework;

class Cookies
{

	public static function SetCookie($name, $value, $expireDays = 30)
	{
		$expire = time() + 60 * 60 * 24 * $expireDays;

		//Si tiene https no importa el entorno, es segura.
		//$secure  = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
		// PHP_URL_SCHEME
		$scheme = parse_url(Context::Settings()->GetMainServerPublicUrl(), PHP_URL_SCHEME);
		$secure = ($scheme == "https");
		// $host = 'aacademica.org';
		$host = parse_url(Context::Settings()->GetMainServerPublicUrl(), PHP_URL_HOST);
		$ret = setcookie($name, $value, $expire, '/', $host, $secure, true);

		if($ret === false)
		{
			$ex = new ErrorException('SetCookie');
			Log::HandleSilentException($ex);
		}
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
		else
			return '';
	}

	public static function DeleteCookie($name)
	{
		self::RenewCookie($name, -365);
		unset($_COOKIE[$name]);
	}

}
