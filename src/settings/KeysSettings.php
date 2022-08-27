<?php

namespace minga\framework\settings;

use Defuse\Crypto\Key;
use minga\framework\Context;
use minga\framework\ErrorException;

class KeysSettings
{
	// Internas para firmado y hashing
	public string $HashKeyedKey = '';
	public string $ExportNavigationKey = '';
	// Utiliza esta key en forma predeterminada.
	public string $RememberKey = '3vAAAGS4lKNFO7PKg1vWAy4SnnalgRatxknIrQJhWDvTqe7QTJV4gZD2w+n3EQdzyfi6gBhq3Xo/eUSp7M92ymMeYuo=';

	// Obtenidas de terceros
	public string $RecaptchaSiteKey = '';
	public string $RecaptchaSecretKey = '';

	public string $GoogleAnalyticsKey = '';
	// puede ser un array, y el hint @var array|string|null no funciona */
	public $GoogleMapsKey = '';
	public string $GoogleGeocodingKey = '';
	public string $AddThisKey = '';
	public string $SendGridApiKey = '';

	public string $MicrosftSpeechToTextKey = '';
	public string $MicrosftSpeechToTextRegion = '';

	/**
	 * Clave secreta random para uso interno
	 * de la aplicación (necesario para Symfony).
	 */
	public string $AppSecret = '';

	// Interna simple para ingresar a test
	public string $TestEnvironmentKey = '';

	public function GetHashKeyedKey() : string
	{
		if($this->HashKeyedKey == '')
			throw new ErrorException(Context::Trans('La clave HashKeyed no está configurada. Agregarla en el archivo /config/settings.php.'));

		return base64_decode($this->HashKeyedKey);
	}

	public function GetGoogleMapsCount() : int
	{
		return 1;
	}

	public function GetGoogleMapsIndex() : int
	{
			return 0;
	}

	public function GetGoogleMapsKey() : string
	{
		return $this->GoogleMapsKey;
	}

	public function CreateNewRememberKey() : string
	{
		$key = Key::createNewRandomKey();
		return base64_encode($key->saveToAsciiSafeString());
	}

	public function GetRememberKey() : string
	{
		if($this->RememberKey == '')
			throw new ErrorException(Context::Trans('La clave Remember no está configurada. Agregarla en el archivo /config/settings.php.'));

		return bin2hex(base64_decode($this->RememberKey));
	}
}
