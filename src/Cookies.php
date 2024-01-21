<?php

namespace minga\framework;

class Cookies
{
	public static function SetCookie(string $name, $value, int $expireDays = 30) : void
	{
		$expire = time() + 60 * 60 * 24 * $expireDays;

		//Si tiene https no importa el entorno, es segura.
		$secure = Request::IsSecure();

		$host = Params::SafeServer('HTTP_HOST');
		if ($host == false)
			$host = parse_url(Context::Settings()->GetPublicUrl(), PHP_URL_HOST);
		if (Str::Contains($host, ":"))
			$host = explode(":", $host)[0];
		$ret = setcookie($name, $value, $expire, '/', $host, $secure, true);

		if($ret === false)
			Log::HandleSilentException(new ErrorException('SetCookie'));
	}

	public static function RenewCookie(string $name, int $expireDays = 30) : void
	{
		$cookie = self::GetCookie($name);
		if($cookie != '')
			self::SetCookie($name, $cookie, $expireDays);
	}

	public static function GetCookie(string $name) : string
	{
		if(isset($_COOKIE[$name]))
			return $_COOKIE[$name];

		return '';
	}

	public static function DeleteCookie(string $name) : void
	{
		self::RenewCookie($name, -365);
		unset($_COOKIE[$name]);
	}
}
