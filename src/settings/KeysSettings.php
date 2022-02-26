<?php

namespace minga\framework\settings;

use Defuse\Crypto\Key;
use minga\framework\Date;
use minga\framework\ErrorException;

class KeysSettings
{
	// Internas para firmado y hashing
	/** @var string */
	public $HashKeyedKey = '';
	/** @var string */
	public $ExportNavigationKey = '';
	// Utiliza esta key en forma predeterminada.
	/** @var string */
	public $RememberKey = '3vAAAGS4lKNFO7PKg1vWAy4SnnalgRatxknIrQJhWDvTqe7QTJV4gZD2w+n3EQdzyfi6gBhq3Xo/eUSp7M92ymMeYuo=';

	// Obtenidas de terceros
	/** @var string */
	public $RecaptchaSiteKey = '';
	/** @var string */
	public $RecaptchaSecretKey = '';

	/** @var string */
	public $GoogleAnalyticsKey = '';
	/** @var array|string|null */
	public $GoogleMapsKey = '';
	/** @var string */
	public $GoogleGeocodingKey = '';
	/** @var string */
	public $AddThisKey = '';
	/** @var string */
	public $SendGridApiKey = '';

	// Interna simple para ingresar a test
	/** @var string */
	public $TestEnvironmentKey = '';

	public function GetHashKeyedKey() : string
	{
		if($this->HashKeyedKey == '')
			throw new ErrorException('HashKeyed key not set. Please, add it to /config/settings.php file.');

		return base64_decode($this->HashKeyedKey);
	}

	public function GetGoogleMapsKey()
	{
		$keys = $this->GoogleMapsKey;
		if (is_array($keys) == false)
			return $keys;
		$day = Date::CurrentDay();
		$step = 30 / count($keys);
		$current = (int)($day / $step);
		if ($current >= count($keys))
			$current = count($keys) - 1;
		return $keys[$current];
	}

	public function CreateNewRememberKey() : string
	{
		$key = Key::createNewRandomKey();
		return base64_encode($key->saveToAsciiSafeString());
	}

	public function GetRememberKey() : string
	{
		if($this->RememberKey == '')
			throw new ErrorException('Remember key not set. Please, add it to /config/settings.php file.');

		return bin2hex(base64_decode($this->RememberKey));
	}
}
