<?php

namespace minga\framework;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;

class GeoIp
{
	private static $geoDb = null;

	// This creates the Reader object, which
	// should be reused across lookups.
	private static function GetGeoDb()
	{
		if(self::$geoDb === null)
		{
			self::$geoDb = new Reader(Context::Paths()->GetFrameworkDataPath()
				. '/GeoLite2-Country/GeoLite2-Country.mmdb');
		}

		return self::$geoDb;
	}

	private static function GetCountry($ip)
	{
		try
		{
			$record = self::GetGeoDb()->country($ip);
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

	public static function GetCountryName($ip)
	{
		if ($ip == '127.0.0.1')
			$ip ='190.55.175.193';

		$country = self::GetCountry($ip);

		if($country !== null)
			return $country->names['es'];
		else
			return '';
	}

	public static function GetClientCountryCode()
	{
		$ip = Params::SafeServer('REMOTE_ADDR');

		$country = self::GetCountry($ip);
		if($country !== null)
			return $country->isoCode;
		else
			return '--';
	}

	public static function GetNameFromCode($cc)
	{
		$countryNames = array(
			'AP' => 'Asia/Región Pacífica',
			'EU' => 'Europa',
			'AD' => 'Andorra',
			'AE' => 'Emiratos Árabes Unidos',
			'AF' => 'Afganistán',
			'AG' => 'Antigua y Barbuda',
			'AI' => 'Anguila',
			'AL' => 'Albania',
			'AM' => 'Armenia',
			'AN' => 'Antillas holandesas',
			'AO' => 'Angola',
			'AQ' => 'Antártida',
			'AR' => 'Argentina',
			'AS' => 'Samoa Americano',
			'AT' => 'Austria',
			'AU' => 'Australia',
			'AW' => 'Aruba',
			'AZ' => 'Azerbaiján',
			'BA' => 'Bosnia Herzegovina',
			'BB' => 'Barbados',
			'BD' => 'Bangla Desh',
			'BE' => 'Bélgica',
			'BF' => 'Burkina Faso',
			'BG' => 'Bulgaria',
			'BH' => 'Bahrein',
			'BI' => 'Burundi',
			'BJ' => 'Benin',
			'BM' => 'Bermudas',
			'BN' => 'Brunei Darussalam',
			'BO' => 'Bolivia',
			'BR' => 'Brasil',
			'BS' => 'Bahamas',
			'BT' => 'Bhután',
			'BV' => 'Isla de Bouvet',
			'BW' => 'Botswana',
			'BY' => 'Bielorrusia',
			'BZ' => 'Belice',
			'CA' => 'Canadá',
			'CC' => 'Islas cocos',
			'CD' => 'Congo, República democrática del',
			'CF' => 'República Centroafricana',
			'CG' => 'Congo',
			'CH' => 'Suiza',
			'CI' => 'Costa de Ivório',
			'CK' => 'Islas Cook',
			'CL' => 'Chile',
			'CM' => 'Camerún',
			'CN' => 'China',
			'CO' => 'Colombia',
			'CR' => 'Costa Rica',
			'CU' => 'Cuba',
			'CV' => 'Cabo Verde',
			'CX' => 'Isla de Navidad',
			'CY' => 'Cipria',
			'CZ' => 'Republica Checa',
			'DE' => 'Alemania',
			'DJ' => 'Djabuti',
			'DK' => 'Dinamarca',
			'DM' => 'Dominica',
			'DO' => 'Republica Dominicana',
			'DZ' => 'Argelia',
			'EC' => 'Ecuador',
			'EE' => 'Estonia',
			'EG' => 'Egipto',
			'EH' => 'Sahara Occidental',
			'ER' => 'Eritrea',
			'ES' => 'España',
			'ET' => 'Etiopía',
			'FI' => 'Finlandia',
			'FJ' => 'Fiji',
			'FK' => 'Islas Malvinas',
			'FM' => 'Estados Federados de la Micronesia',
			'FO' => 'Islas de Faroe',
			'FR' => 'Francia',
			'FX' => 'Francia, Metropolitana',
			'GA' => 'Gabón',
			'GB' => 'Reino Unido',
			'GD' => 'Granada',
			'GE' => 'Georgia',
			'GF' => 'Guayana Francesa',
			'GH' => 'Ghana',
			'GI' => 'Gibraltar',
			'GL' => 'Groenlandia',
			'GM' => 'Gambia',
			'GN' => 'Guinea',
			'GP' => 'Guadalupe',
			'GQ' => 'Guinea Ecuatorial',
			'GR' => 'Grecia',
			'GS' => 'Georgia Sur e Islas Sándwich del Sur',
			'GT' => 'Guatemala',
			'GU' => 'Guam',
			'GW' => 'Guinea-Bissau',
			'GY' => 'Guyana',
			'HK' => 'Hong Kong',
			'HM' => 'Islas Heard y McDonald',
			'HN' => 'Honduras',
			'HR' => 'Croacia',
			'HT' => 'Haití',
			'HU' => 'Hungría',
			'ID' => 'Indonesia',
			'IE' => 'Irlanda',
			'IL' => 'Israel',
			'IN' => 'India',
			'IO' => 'Territorio Británico del Océano Índico',
			'IQ' => 'Iraq',
			'IR' => 'República Islámica de Irán',
			'IS' => 'Islandia',
			'IT' => 'Italia',
			'JM' => 'Jamaica',
			'JO' => 'Jordán',
			'JP' => 'Japón',
			'KE' => 'Kenya',
			'KG' => 'Kyrgyzstán',
			'KH' => 'Camboya',
			'KI' => 'Kiribati',
			'KM' => 'Comores',
			'KN' => 'San Kitts y Nevis',
			'KP' => 'Corea, República Democrática del Pueblo de',
			'KR' => 'Corea, República de',
			'KW' => 'Kuwait',
			'KY' => 'Islas Caimán',
			'KZ' => 'Kazajstán',
			'LA' => 'República Democrática del Pueblo de Lao',
			'LB' => 'El Líbano',
			'LC' => 'Santa Lucía',
			'LI' => 'Liechtenstein',
			'LK' => 'Sri Lanka',
			'LR' => 'Liberia',
			'LS' => 'Lesotho',
			'LT' => 'Lituania',
			'LU' => 'Luxemburgo',
			'LV' => 'Latvia',
			'LY' => 'Jamahiriya Árabe Libio',
			'MA' => 'Marruecos',
			'MC' => 'Mónaco',
			'MD' => 'República de Moldavia',
			'MG' => 'Madagascar',
			'MH' => 'Islas Marshall',
			'MK' => 'Macedonia',
			'ML' => 'Malí',
			'MM' => 'Myanmar',
			'MN' => 'Mongolia',
			'MO' => 'Macao',
			'MP' => 'Islas de Mariana Norteñas',
			'MQ' => 'Martinica',
			'MR' => 'Mauritania',
			'MS' => 'Montserrat',
			'MT' => 'Malta',
			'MU' => 'Mauricio',
			'MV' => 'Maldivas',
			'MW' => 'Malawi',
			'MX' => 'México',
			'MY' => 'Malasia',
			'MZ' => 'Mozambique',
			'NA' => 'Namibia',
			'NC' => 'Nueva Caledonia',
			'NE' => 'Níger',
			'NF' => 'Isla Norfolk',
			'NG' => 'Nigeria',
			'NI' => 'Nicaragua',
			'NL' => 'Holanda',
			'NO' => 'Noruega',
			'NP' => 'Nepal',
			'NR' => 'Nauru',
			'NU' => 'Niue',
			'NZ' => 'Nueva Zelanda',
			'OM' => 'Omán',
			'PA' => 'Panamá',
			'PE' => 'Perú',
			'PF' => 'Polinesia Francesa',
			'PG' => 'Papúa Nueva Guinea',
			'PH' => 'Filipinas',
			'PK' => 'Pakistán',
			'PL' => 'Polonia',
			'PM' => 'Pedro y Miquelón',
			'PN' => 'Islas Pitcairn',
			'PR' => 'Puerto Rico',
			'PS' => 'Territorio Palestino Ocupado',
			'PT' => 'Portugal',
			'PW' => 'Palau',
			'PY' => 'Paraguay',
			'QA' => 'Qatar',
			'RE' => 'Reunión',
			'RO' => 'Rumania',
			'RU' => 'Federación Rusa',
			'RW' => 'Ruanda',
			'SA' => 'Arabia Saudita',
			'SB' => 'Islas Salomón',
			'SC' => 'Seychelles',
			'SD' => 'Sudán',
			'SE' => 'Suecia',
			'SG' => 'Singapur',
			'SH' => 'Santa Helena',
			'SI' => 'Eslovenia',
			'SJ' => 'Islas Svalbard y Jan Mayen',
			'SK' => 'Eslovaquia',
			'SL' => 'Sierra Leone',
			'SM' => 'San Marino',
			'SN' => 'Senegal',
			'SO' => 'Somalia',
			'SR' => 'Surinam',
			'ST' => 'Sao Tome y Príncipe',
			'SV' => 'El Salvador',
			'SY' => 'República Árabe Siria',
			'SZ' => 'Swazilandia',
			'TC' => 'Islas Turcas y Caicos',
			'TD' => 'Chad',
			'TF' => 'Territorios Sureños Franceses',
			'TG' => 'Togo',
			'TH' => 'Tailandia',
			'TJ' => 'Tayikistán',
			'TK' => 'Tokelau',
			'TM' => 'Turkmenistán',
			'TN' => 'Túnez',
			'TO' => 'Tonga',
			'TP' => 'Timor Oriental',
			'TR' => 'Turquía',
			'TT' => 'Trinidad y Tobago',
			'TV' => 'Tuvalu',
			'TW' => 'Taiwán',
			'TZ' => 'Taiwán, Provincia de China',
			'UA' => 'Ucrania',
			'UG' => 'Uganda',
			'UM' => 'Islas Remotas Menores de Estados Unidos',
			'US' => 'Estados Unidos',
			'UY' => 'Uruguay',
			'UZ' => 'Uzbekistán',
			'VA' => 'Estado de la Ciudad del Vaticano',
			'VC' => 'San Vicente y los Granadinos',
			'VE' => 'Venezuela',
			'VG' => 'Islas Vírgenes Británicas',
			'VI' => 'Islas Vírgenes Americanas',
			'VN' => 'Vietnam',
			'VU' => 'Vanuatu',
			'WF' => 'Islas Wallis y Futuna',
			'WS' => 'Samoa',
			'YE' => 'República del Yemen',
			'YT' => 'Mayotte',
			'YU' => 'Yugoslavia',
			'ZA' => 'Sudáfrica',
			'ZM' => 'Zambia',
			'ZR' => 'Zaire',
			'ZW' => 'Zimbabwe',
			'A1' => 'Proxy Anónimo',
			'A2' => 'Proveedor de Satélite',
			'O1' => 'Otro',
		);

		if (array_key_exists(Str::ToUpper($cc), $countryNames))
			return $countryNames[Str::ToUpper($cc)];
		else
			return $cc;
	}
}
