<?php

namespace minga\framework\settings;

use Defuse\Crypto\Key;
use minga\framework\Context;
use minga\framework\Date;
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
	/** @var array|string|null */
	// puede ser un array, y el hint no funciona
	public $GoogleMapsKey = '';
	public $FixedGoogleKey = -1;
	public string $GoogleGeocodingKey = '';
	public string $AddThisKey = '';
	public string $SendGridApiKey = '';

	public string $RemoteBackupAuthKey = '';
	public string $DeploymentAuthKey = '';
	public string $RemoteEmailKey = '';

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

	public function Ofuscate($texto, $key = 'salt') : string
	{
		if ($texto == '' || $texto == null)
			return '';
		// Convertimos texto y clave a arrays de bytes
		$textoBytes = unpack('C*', $texto);
		$keyBytes = unpack('C*', $key);
		$keyLength = count($keyBytes);

		// Ofuscamos cada byte del texto usando la clave
		$resultado = '';
		foreach ($textoBytes as $index => $byte) {
			// Obtenemos el byte correspondiente de la clave
			$keyIndex = (($index - 1) % $keyLength) + 1;
			$keyByte = $keyBytes[$keyIndex];

			// XOR entre el byte del texto y el byte de la clave
			$ofuscado = $byte ^ $keyByte;

			// Convertimos a hexadecimal y añadimos al resultado
			$resultado .= sprintf('%02x', $ofuscado);
		}
		return $resultado;
	}

	public function IsRemoteBackupAuthKeyValid($key) : bool
	{
		return $this->RemoteBackupAuthKey != ''
			&& hash_equals($this->RemoteBackupAuthKey, $key);
	}

	public function IsDeploymentAuthKeyValid($key) : bool
	{
		return $this->DeploymentAuthKey != ''
			&& hash_equals($this->DeploymentAuthKey, $key);
	}

	public function GetGoogleMapsCount() : int
	{
		$keys = $this->GoogleMapsKey;
		if (is_array($keys) == false)
			return 1;

		return count($keys);
	}

	public function GetGoogleMapsIndex() : int
	{
		$keys = $this->GoogleMapsKey;
		if (is_array($keys) == false)
			return 0;
		if ($this->FixedGoogleKey != -1)
			$current = $this->FixedGoogleKey;
		else {
			$day = Date::CurrentDay();
			$step = 36 / count($keys);
			$current = (int)($day / $step);
			if (count($keys) == 3 && $day == 11)
				$current = 1;
		}
		if ($current >= count($keys))
			$current = count($keys) - 1;
		return $current;
	}

	public function GetGoogleMapsKey()
	{
		$keys = $this->GoogleMapsKey;
		if (is_array($keys) == false)
			return $keys;

		return $keys[self::GetGoogleMapsIndex()];
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
