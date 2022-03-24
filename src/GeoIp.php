<?php

namespace minga\framework;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use GeoIp2\Record\City;
use GeoIp2\Record\Country;

class GeoIp
{
	private static Reader $geoDbCity;
	private static Reader $geoDbCountry;

	public static function GetCurrentLatLong() : ?array
	{
		try
		{
			$addr = Params::SafeServer('REMOTE_ADDR');
			if ($addr === '127.0.0.1' || self::IpIsPrivate($addr))
			{
				// Si estoy en el servidor de desarrollo, o navegando local, busco mi ip externa.
				$conn = new WebConnection();
				$conn->Initialize();
				$response = $conn->Get('https://api.ipify.org?format=json');
				$myIp = json_decode($response->GetString(), true);
				$conn->Finalize();
				$addr = $myIp['ip'];
			}
			$location = self::GetCityLocation($addr);

			if ($location === null)
				$location = self::GetIpFromGeoPluginWebService($addr);

			return $location;
		}
		catch(\Exception $e)
		{
			Log::HandleSilentException($e);
			return null;
		}
	}

	public static function GetCityDatabaseDatetime() : \DateTime
	{
		$c = self::GetGeoDbCity();
		$ret = new \DateTime();
		$ret->setTimestamp($c->metadata()->buildEpoch);
		return $ret;
	}

	public static function GetCountryDatabaseDatetime() : \DateTime
	{
		$c = self::GetGeoDbCountry();
		$ret = new \DateTime();
		$ret->setTimestamp($c->metadata()->buildEpoch);
		return $ret;
	}

	private static function GetIpFromGeoPluginWebService(string $addr) : ?array
	{
		$path = 'http://www.geoplugin.net/php.gp?ip=' . $addr;
		$geoplugin = unserialize(file_get_contents($path));
		if (is_numeric($geoplugin['geoplugin_latitude']) && is_numeric($geoplugin['geoplugin_longitude']))
		{
			$lat = $geoplugin['geoplugin_latitude'];
			$long = $geoplugin['geoplugin_longitude'];
			return ['lat' => $lat, 'lon' => $long];
		}
		return null;
	}

	private static function IpIsPrivate($ip) : bool
	{
		$priAddrs = [
			'10.0.0.0|10.255.255.255', // single class A network
			'172.16.0.0|172.31.255.255', // 16 contiguous class B network
			'192.168.0.0|192.168.255.255', // 256 contiguous class C network
			'169.254.0.0|169.254.255.255', // Link-local address also refered to as Automatic Private IP Addressing
			'127.0.0.0|127.255.255.255', // localhost
		];

		$longIp = ip2long($ip);
		if ($longIp != -1)
		{
			foreach ($priAddrs as $priAddr)
			{
				[$start, $end] = explode('|', $priAddr);

				// IF IS PRIVATE
				if ($longIp >= ip2long($start) && $longIp <= ip2long($end))
					return true;
			}
		}
		return false;
	}

	private static function GetGeoDbCountry() : Reader
	{
		if(isset(self::$geoDbCountry) == false)
		{
			$file = self::SolvePath('GeoLite2-Country/GeoLite2-Country.mmdb');
			self::$geoDbCountry = new Reader($file);
		}
		return self::$geoDbCountry;
	}

	private static function SolvePath(string $path) : string
	{
		$dir1 = Context::Paths()->GetFrameworkDataPath();
		if (file_exists($dir1 . '/' . $path))
			return $dir1 . '/' . $path;
		$dir2 = Context::Paths()->GetStorageRoot() . '/geoip';
		if (file_exists($dir2 . '/' . $path))
			return $dir2 . '/' . $path;
		throw new ErrorException(Context::Trans('Directorio no encontrado para ') . $path);
	}

	private static function GetGeoDbCity() : Reader
	{
		if(isset(self::$geoDbCity) == false)
		{
			$file = self::SolvePath('GeoLite2-City/GeoLite2-City.mmdb');
			self::$geoDbCity = new Reader($file);
		}
		return self::$geoDbCity;
	}

	private static function GetCityLocation(string $ip) : ?array
	{
		try
		{
			$db = self::GetGeoDbCity();
			if ($db == false)
				return null;
			$record = $db->city($ip);

			return ['lat' => $record->location->latitude, 'lon' => $record->location->longitude];
		}
		catch(AddressNotFoundException $e)
		{
			return null;
		}
		catch(\InvalidArgumentException $e)
		{
			return null;
		}
	}

	public static function GetCity(string $ip) : ?City
	{
		if ($ip == '')
			return null;
		try
		{
			$record = self::GetGeoDbCity()->city($ip);
			return $record->city;
		}
		catch(AddressNotFoundException $e)
		{
			return null;
		}
		catch(\InvalidArgumentException $e)
		{
			return null;
		}
	}

	public static function GetSubdivisions(string $ip)
	{
		try
		{
			$record = self::GetGeoDbCity()->city($ip);
			return $record->subdivisions;
		}
		catch(AddressNotFoundException $e)
		{
			return null;
		}
		catch(\InvalidArgumentException $e)
		{
			return null;
		}
	}

	public static function GetCountry(string $ip) : ?Country
	{
		try
		{
			if ($ip == '')
				return null;
			$record = self::GetGeoDbCountry()->country($ip);
			return $record->country;
		}
		catch(AddressNotFoundException $e)
		{
			return null;
		}
		catch(\InvalidArgumentException $e)
		{
			return null;
		}
	}

	public static function GetCountryName(string $ip) : ?string
	{
		if ($ip == '')
			return null;
		if ($ip == '127.0.0.1')
			$ip = '190.55.175.193';

		$country = self::GetCountry($ip);

		if($country !== null)
			return $country->names['es'];
		return '';
	}

	public static function GetClientCountryCode() : string
	{
		$ip = Params::SafeServer('REMOTE_ADDR');
		if ($ip == '')
			return '--';
		$country = self::GetCountry($ip);
		if($country !== null && $country->isoCode !== null)
			return $country->isoCode;
		return '--';
	}

	public static function GetNameFromCode(string $cc) : string
	{
		$countryNames = [
			'AP' => Context::Trans('Asia/Región Pacífica'),
			'EU' => Context::Trans('Europa'),
			'AD' => Context::Trans('Andorra'),
			'AE' => Context::Trans('Emiratos Árabes Unidos'),
			'AF' => Context::Trans('Afganistán'),
			'AG' => Context::Trans('Antigua y Barbuda'),
			'AI' => Context::Trans('Anguila'),
			'AL' => Context::Trans('Albania'),
			'AM' => Context::Trans('Armenia'),
			'AN' => Context::Trans('Antillas holandesas'),
			'AO' => Context::Trans('Angola'),
			'AQ' => Context::Trans('Antártida'),
			'AR' => Context::Trans('Argentina'),
			'AS' => Context::Trans('Samoa Americano'),
			'AT' => Context::Trans('Austria'),
			'AU' => Context::Trans('Australia'),
			'AW' => Context::Trans('Aruba'),
			'AZ' => Context::Trans('Azerbaiján'),
			'BA' => Context::Trans('Bosnia Herzegovina'),
			'BB' => Context::Trans('Barbados'),
			'BD' => Context::Trans('Bangla Desh'),
			'BE' => Context::Trans('Bélgica'),
			'BF' => Context::Trans('Burkina Faso'),
			'BG' => Context::Trans('Bulgaria'),
			'BH' => Context::Trans('Bahrein'),
			'BI' => Context::Trans('Burundi'),
			'BJ' => Context::Trans('Benin'),
			'BM' => Context::Trans('Bermudas'),
			'BN' => Context::Trans('Brunei Darussalam'),
			'BO' => Context::Trans('Bolivia'),
			'BR' => Context::Trans('Brasil'),
			'BS' => Context::Trans('Bahamas'),
			'BT' => Context::Trans('Bhután'),
			'BV' => Context::Trans('Isla de Bouvet'),
			'BW' => Context::Trans('Botswana'),
			'BY' => Context::Trans('Bielorrusia'),
			'BZ' => Context::Trans('Belice'),
			'CA' => Context::Trans('Canadá'),
			'CC' => Context::Trans('Islas cocos'),
			'CD' => Context::Trans('Congo, República democrática del'),
			'CF' => Context::Trans('República Centroafricana'),
			'CG' => Context::Trans('Congo'),
			'CH' => Context::Trans('Suiza'),
			'CI' => Context::Trans('Costa de Ivório'),
			'CK' => Context::Trans('Islas Cook'),
			'CL' => Context::Trans('Chile'),
			'CM' => Context::Trans('Camerún'),
			'CN' => Context::Trans('China'),
			'CO' => Context::Trans('Colombia'),
			'CR' => Context::Trans('Costa Rica'),
			'CU' => Context::Trans('Cuba'),
			'CV' => Context::Trans('Cabo Verde'),
			'CX' => Context::Trans('Isla de Navidad'),
			'CY' => Context::Trans('Cipria'),
			'CZ' => Context::Trans('Republica Checa'),
			'DE' => Context::Trans('Alemania'),
			'DJ' => Context::Trans('Djabuti'),
			'DK' => Context::Trans('Dinamarca'),
			'DM' => Context::Trans('Dominica'),
			'DO' => Context::Trans('Republica Dominicana'),
			'DZ' => Context::Trans('Argelia'),
			'EC' => Context::Trans('Ecuador'),
			'EE' => Context::Trans('Estonia'),
			'EG' => Context::Trans('Egipto'),
			'EH' => Context::Trans('Sahara Occidental'),
			'ER' => Context::Trans('Eritrea'),
			'ES' => Context::Trans('España'),
			'ET' => Context::Trans('Etiopía'),
			'FI' => Context::Trans('Finlandia'),
			'FJ' => Context::Trans('Fiji'),
			'FK' => Context::Trans('Islas Malvinas'),
			'FM' => Context::Trans('Estados Federados de la Micronesia'),
			'FO' => Context::Trans('Islas de Faroe'),
			'FR' => Context::Trans('Francia'),
			'FX' => Context::Trans('Francia, Metropolitana'),
			'GA' => Context::Trans('Gabón'),
			'GB' => Context::Trans('Reino Unido'),
			'GD' => Context::Trans('Granada'),
			'GE' => Context::Trans('Georgia'),
			'GF' => Context::Trans('Guayana Francesa'),
			'GH' => Context::Trans('Ghana'),
			'GI' => Context::Trans('Gibraltar'),
			'GL' => Context::Trans('Groenlandia'),
			'GM' => Context::Trans('Gambia'),
			'GN' => Context::Trans('Guinea'),
			'GP' => Context::Trans('Guadalupe'),
			'GQ' => Context::Trans('Guinea Ecuatorial'),
			'GR' => Context::Trans('Grecia'),
			'GS' => Context::Trans('Georgia Sur e Islas Sándwich del Sur'),
			'GT' => Context::Trans('Guatemala'),
			'GU' => Context::Trans('Guam'),
			'GW' => Context::Trans('Guinea-Bissau'),
			'GY' => Context::Trans('Guyana'),
			'HK' => Context::Trans('Hong Kong'),
			'HM' => Context::Trans('Islas Heard y McDonald'),
			'HN' => Context::Trans('Honduras'),
			'HR' => Context::Trans('Croacia'),
			'HT' => Context::Trans('Haití'),
			'HU' => Context::Trans('Hungría'),
			'ID' => Context::Trans('Indonesia'),
			'IE' => Context::Trans('Irlanda'),
			'IL' => Context::Trans('Israel'),
			'IN' => Context::Trans('India'),
			'IO' => Context::Trans('Territorio Británico del Océano Índico'),
			'IQ' => Context::Trans('Iraq'),
			'IR' => Context::Trans('República Islámica de Irán'),
			'IS' => Context::Trans('Islandia'),
			'IT' => Context::Trans('Italia'),
			'JM' => Context::Trans('Jamaica'),
			'JO' => Context::Trans('Jordán'),
			'JP' => Context::Trans('Japón'),
			'KE' => Context::Trans('Kenya'),
			'KG' => Context::Trans('Kyrgyzstán'),
			'KH' => Context::Trans('Camboya'),
			'KI' => Context::Trans('Kiribati'),
			'KM' => Context::Trans('Comores'),
			'KN' => Context::Trans('San Kitts y Nevis'),
			'KP' => Context::Trans('Corea, República Democrática del Pueblo de'),
			'KR' => Context::Trans('Corea, República de'),
			'KW' => Context::Trans('Kuwait'),
			'KY' => Context::Trans('Islas Caimán'),
			'KZ' => Context::Trans('Kazajstán'),
			'LA' => Context::Trans('República Democrática del Pueblo de Lao'),
			'LB' => Context::Trans('El Líbano'),
			'LC' => Context::Trans('Santa Lucía'),
			'LI' => Context::Trans('Liechtenstein'),
			'LK' => Context::Trans('Sri Lanka'),
			'LR' => Context::Trans('Liberia'),
			'LS' => Context::Trans('Lesotho'),
			'LT' => Context::Trans('Lituania'),
			'LU' => Context::Trans('Luxemburgo'),
			'LV' => Context::Trans('Latvia'),
			'LY' => Context::Trans('Jamahiriya Árabe Libio'),
			'MA' => Context::Trans('Marruecos'),
			'MC' => Context::Trans('Mónaco'),
			'MD' => Context::Trans('República de Moldavia'),
			'MG' => Context::Trans('Madagascar'),
			'MH' => Context::Trans('Islas Marshall'),
			'MK' => Context::Trans('Macedonia'),
			'ML' => Context::Trans('Malí'),
			'MM' => Context::Trans('Myanmar'),
			'MN' => Context::Trans('Mongolia'),
			'MO' => Context::Trans('Macao'),
			'MP' => Context::Trans('Islas de Mariana Norteñas'),
			'MQ' => Context::Trans('Martinica'),
			'MR' => Context::Trans('Mauritania'),
			'MS' => Context::Trans('Montserrat'),
			'MT' => Context::Trans('Malta'),
			'MU' => Context::Trans('Mauricio'),
			'MV' => Context::Trans('Maldivas'),
			'MW' => Context::Trans('Malawi'),
			'MX' => Context::Trans('México'),
			'MY' => Context::Trans('Malasia'),
			'MZ' => Context::Trans('Mozambique'),
			'NA' => Context::Trans('Namibia'),
			'NC' => Context::Trans('Nueva Caledonia'),
			'NE' => Context::Trans('Níger'),
			'NF' => Context::Trans('Isla Norfolk'),
			'NG' => Context::Trans('Nigeria'),
			'NI' => Context::Trans('Nicaragua'),
			'NL' => Context::Trans('Holanda'),
			'NO' => Context::Trans('Noruega'),
			'NP' => Context::Trans('Nepal'),
			'NR' => Context::Trans('Nauru'),
			'NU' => Context::Trans('Niue'),
			'NZ' => Context::Trans('Nueva Zelanda'),
			'OM' => Context::Trans('Omán'),
			'PA' => Context::Trans('Panamá'),
			'PE' => Context::Trans('Perú'),
			'PF' => Context::Trans('Polinesia Francesa'),
			'PG' => Context::Trans('Papúa Nueva Guinea'),
			'PH' => Context::Trans('Filipinas'),
			'PK' => Context::Trans('Pakistán'),
			'PL' => Context::Trans('Polonia'),
			'PM' => Context::Trans('Pedro y Miquelón'),
			'PN' => Context::Trans('Islas Pitcairn'),
			'PR' => Context::Trans('Puerto Rico'),
			'PS' => Context::Trans('Territorio Palestino Ocupado'),
			'PT' => Context::Trans('Portugal'),
			'PW' => Context::Trans('Palau'),
			'PY' => Context::Trans('Paraguay'),
			'QA' => Context::Trans('Qatar'),
			'RE' => Context::Trans('Reunión'),
			'RO' => Context::Trans('Rumania'),
			'RU' => Context::Trans('Federación Rusa'),
			'RW' => Context::Trans('Ruanda'),
			'SA' => Context::Trans('Arabia Saudita'),
			'SB' => Context::Trans('Islas Salomón'),
			'SC' => Context::Trans('Seychelles'),
			'SD' => Context::Trans('Sudán'),
			'SE' => Context::Trans('Suecia'),
			'SG' => Context::Trans('Singapur'),
			'SH' => Context::Trans('Santa Helena'),
			'SI' => Context::Trans('Eslovenia'),
			'SJ' => Context::Trans('Islas Svalbard y Jan Mayen'),
			'SK' => Context::Trans('Eslovaquia'),
			'SL' => Context::Trans('Sierra Leone'),
			'SM' => Context::Trans('San Marino'),
			'SN' => Context::Trans('Senegal'),
			'SO' => Context::Trans('Somalia'),
			'SR' => Context::Trans('Surinam'),
			'ST' => Context::Trans('Sao Tome y Príncipe'),
			'SV' => Context::Trans('El Salvador'),
			'SY' => Context::Trans('República Árabe Siria'),
			'SZ' => Context::Trans('Swazilandia'),
			'TC' => Context::Trans('Islas Turcas y Caicos'),
			'TD' => Context::Trans('Chad'),
			'TF' => Context::Trans('Territorios Sureños Franceses'),
			'TG' => Context::Trans('Togo'),
			'TH' => Context::Trans('Tailandia'),
			'TJ' => Context::Trans('Tayikistán'),
			'TK' => Context::Trans('Tokelau'),
			'TM' => Context::Trans('Turkmenistán'),
			'TN' => Context::Trans('Túnez'),
			'TO' => Context::Trans('Tonga'),
			'TP' => Context::Trans('Timor Oriental'),
			'TR' => Context::Trans('Turquía'),
			'TT' => Context::Trans('Trinidad y Tobago'),
			'TV' => Context::Trans('Tuvalu'),
			'TW' => Context::Trans('Taiwán'),
			'TZ' => Context::Trans('Taiwán, Provincia de China'),
			'UA' => Context::Trans('Ucrania'),
			'UG' => Context::Trans('Uganda'),
			'UM' => Context::Trans('Islas Remotas Menores de Estados Unidos'),
			'US' => Context::Trans('Estados Unidos'),
			'UY' => Context::Trans('Uruguay'),
			'UZ' => Context::Trans('Uzbekistán'),
			'VA' => Context::Trans('Estado de la Ciudad del Vaticano'),
			'VC' => Context::Trans('San Vicente y los Granadinos'),
			'VE' => Context::Trans('Venezuela'),
			'VG' => Context::Trans('Islas Vírgenes Británicas'),
			'VI' => Context::Trans('Islas Vírgenes Americanas'),
			'VN' => Context::Trans('Vietnam'),
			'VU' => Context::Trans('Vanuatu'),
			'WF' => Context::Trans('Islas Wallis y Futuna'),
			'WS' => Context::Trans('Samoa'),
			'YE' => Context::Trans('República del Yemen'),
			'YT' => Context::Trans('Mayotte'),
			'YU' => Context::Trans('Yugoslavia'),
			'ZA' => Context::Trans('Sudáfrica'),
			'ZM' => Context::Trans('Zambia'),
			'ZR' => Context::Trans('Zaire'),
			'ZW' => Context::Trans('Zimbabwe'),
			'A1' => Context::Trans('Proxy Anónimo'),
			'A2' => Context::Trans('Proveedor de Satélite'),
			'O1' => Context::Trans('Otro'),
		];

		if (isset($countryNames[Str::ToUpper($cc)]))
			return $countryNames[Str::ToUpper($cc)];
		return $cc;
	}
}
