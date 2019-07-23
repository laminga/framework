<?php

namespace minga\framework\settings;

use minga\framework\ErrorException;

class KeysSettings
{
	// Internas para firmado y hashing
	public $RememberKey = '';
	public $HashKeyedKey = '';
	public $ExportNavigationKey = '';

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

	public function GetRememberKey()
	{
		if($this->RememberKey == '')
			throw new ErrorException('Remember key not set. Please, add it to /config/settings.php file.');

		return bin2hex(base64_decode($this->RememberKey));
	}
}
