<?php

namespace minga\framework\security;

use minga\framework\Context;
use minga\framework\Params;
use minga\framework\Str;
use minga\framework\WebConnection;

class SecureTransport
{
	private static function HashKeyed(string $cad) : string
	{
		return hash_hmac('sha256', $cad, Context::Settings()->Keys()->GetHashKeyedKey());
	}

	public static function GetUrlWithHmac(string $uri, string $host = '') : string
	{
		$rnd = self::CreateId(16);
		if($host == '')
			$host = Context::Settings()->GetPublicUrl();
		if(Str::EndsWith($host, '/'))
			$host = Str::RemoveEnding($host, '/');
		if(Str::Contains($uri, '?') == false)
			$uri .= '?';
		if(Str::StartsWith($uri, '/') == false)
			$uri = '/' . $uri;

		$url = $host . $uri . "&rnd=" . $rnd;
		$hash = self::HashKeyed($url);
		return $url . "&hmac=" . $hash;
	}

	public static function CreateId(int $len = 12) : string
	{
		return bin2hex(random_bytes($len));
	}

	public static function UriHashIsValid() : bool
	{
		$uri = Params::SafeServer('REQUEST_URI');
		if($uri == '')
			return false;

		$parts = explode('&hmac=', $uri);
		if(count($parts) != 2)
			return false;

		$serverUrl = Context::Settings()->Servers()->Current()->publicUrl;
		if(Str::EndsWith($serverUrl, '/'))
			$serverUrl = Str::RemoveEnding($serverUrl, '/');

		$hashCheck = self::HashKeyed($serverUrl . $parts[0]);
		return hash_equals($parts[1], $hashCheck);
	}

	public static function HashParams(?array $params, string $rnd = '') : string
	{
		if($rnd == '')
			$rnd = self::CreateId(16);
		if($params == null)
			return self::HashKeyed($rnd);

		$str = WebConnection::PreparePostValues($params);
		return self::HashKeyed($str . $rnd);
	}

	public static function PostHashIsValid() : bool
	{
		$hmac = Params::SafePost('hmac');
		if($hmac == '')
			return false;

		unset($_POST['hmac']);
		$rnd = Params::SafeGet('rnd');
		if($rnd == '')
			return false;

		$hashCheck = self::HashParams($_POST, $rnd);
		return hash_equals($hmac, $hashCheck);
	}
}

