<?php


namespace minga\framework\settings;
use minga\framework\ErrorException;

class KeysSettings
{
	//No usar directamente,
	//usar los métodos GetXxxKey()
	public $rememberKey = '';
	public $hashKeyedKey = '';

	public $recaptchaKey = '';
	public $exportNavigationKey = '';

	public function GetHashKeyedKey()
	{
		if($this->hashKeyedKey == '')
			throw new ErrorException('HashKeyed key not set. Please, add it to /config/settings.php file.');

		return base64_decode($this->hashKeyedKey);
	}

	public function GetRememberKey()
	{
		if($this->rememberKey == '')
			throw new ErrorException('Remember key not set. Please, add it to /config/settings.php file.');

		return bin2hex(base64_decode($this->rememberKey));
	}
}
