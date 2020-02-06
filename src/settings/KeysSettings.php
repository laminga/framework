<?php

namespace minga\framework\settings;

use minga\framework\ErrorException;
use Defuse\Crypto\Key;

class KeysSettings
{
	// Internas para firmado y hashing
	public $HashKeyedKey = '';
	public $ExportNavigationKey = '';
	// Utiliza esta key en forma predeterminada. 
	public $RememberKey = '3vAAAGS4lKNFO7PKg1vWAy4SnnalgRatxknIrQJhWDvTqe7QTJV4gZD2w+n3EQdzyfi6gBhq3Xo/eUSp7M92ymMeYuo=';

	// Obtenidas de terceros
	public $RecaptchaKey = '';
	public $GoogleAnalyticsKey = '';
	public $GoogleMapsKey = '';
	public $AddThisKey = '';
	public $SendGridApiKey = '';

	public function GetHashKeyedKey()
	{
		if($this->HashKeyedKey == '')
			throw new ErrorException('HashKeyed key not set. Please, add it to /config/settings.php file.');

		return base64_decode($this->HashKeyedKey);
	}

	public function CreateNewRememberKey()
	{
		$key = Key::createNewRandomKey();
		return base64_encode($key->saveToAsciiSafeString());
	}

	public function GetRememberKey()
	{
		if($this->RememberKey == '')
			throw new ErrorException('Remember key not set. Please, add it to /config/settings.php file.');

		return bin2hex(base64_decode($this->RememberKey));
	}
}
